<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>团长查看订单详情</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=yes"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/index.css?t={pigcms{:time()}"/>
		<style type="text/css">
			
			.address-box img{
				width: 30px;
				height: 30px;
				display: inline;
				border-radius: 50%;
				margin-right: 10px;
				vertical-align: middle;
			}

			.total-box .btn.unfinish{
				background:#ff4c29;
			}

			.total-box .btn.finish{
				background:#bbb;
			}

			.mask{
				position: fixed;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				background: rgba(0, 0, 0, .6);
			}

			.mask-box{
				position: relative;
			}

			.mask-content{
				box-sizing: border-box;
				width: 80%;
				padding:30px;
				background: #ddd;
				border-radius: 4px;
				position: relative;
				margin: 0 auto;
				position: absolute;
				top: 50%;
				left: 50%;
				transform: translate(-50%, -50%);
			}

			.mask-content .phone-box{
				background: #fff;
				height: 40px;
				line-height: 40px;
				font-size: 16px;
				color: #61c945;
				border-radius: 4px;
				padding: 0 15px;
			}

			.mask-content .icon-cancel{
				position: absolute;
				right: 5px;
				top: 0;
				font-size: 24px;
				color: #8da985;
			}

			.mask-content .icon-cancel:after {
				position: absolute;
				top: -10px;
				right: -10px;
				bottom: -10px;
				left: -10px;
			}
			.orders_list li { 
				padding: 0px 10px 20px 10px; 
				position: relative;
				
			}
			.orders_list li .time {
				color: 999999;
				font-size: 12px;
				padding-left: 18px;
				background: url({pigcms{$static_path}shop/images/2ddzt_03.png) left center no-repeat;
				background-size: 11px;
				line-height: 1;
				padding-bottom: 8px;
			}
			.orders_list li .p18 {
				padding: 5px 0px 5px 22px;
				position: relative;
			}
			.orders_list li .p18:before {
				content: "";
				width: 8px;
				height: 8px;
				display: block;
				border-radius: 100%;
				background: #2ecc71;
				position: absolute;
				left: 2px;
				top: 50%;
				margin-top: -4px;
				z-index: 2;
			}
			.orders_list li .p18 .con {
				background: #fff;
				padding: 13px;
				border-radius: 5px;
				box-shadow: 0px 0px 8px 2px #e3f0ec;
				position: relative;
			}
			.orders_list{
				padding:20px 0
			}
			.orders_list li:after {
				content: '';
				height: 100%;
				width: 1px;
				background: #dcdcdc;
				position: absolute;
				left: 15px;
				top: 0px;
				z-index: -1;
			}
			.coupon_mask {
				position: fixed;
				top: 0px;
				left: 0px;
				background: rgba(0,0,0,0.7);
				z-index: 888;
				width: 100%;
				height: 100%;
			}
			.status_model {
				overflow-y: scroll;
				max-height: 75%;
				min-height: 28%;
				position: fixed;
				z-index: 5000;
				width: 80%;
				max-width: 300px;
				top: 50%;
				left: 50%;
				-webkit-transform: translate(-50%,-50%);
				transform: translate(-50%,-50%);
				background-color: #fff;
				text-align: center;
				border-radius: 3px;
				overflow-x: hidden;
			}
			.orders_list li .p18 .con p {
				color: #666666;
				font-size: 12px;
				line-height: 18px;
				text-align: justify;
			}
			.orders_list li .p18 .con h2 {
				color: #2ecc71;
				font-size: 14px;
				font-weight: bold;
				padding-bottom: 8px;
			}
			.close{
				float: right;
				padding-right: 13px;
				padding-top: -5px;
				font-size: 24px;
				margin-top: 15px
			}
            .order-page .line_discount {
                height: 35px;
                line-height: 35px;
                padding: 0 20px 0 15px;
                background: #fff;
                margin-bottom: 5px;
            }
            .vipMoney{
                font-weight: bold;
                margin: 0px 10px 0px 5px;
                color:#777;
                position:relative;
            }
            .vipMoney:after{
                content: ' ';
                position: absolute;
                left: 0;
                right: 0;
                top: 49%;
                height: 1px;
                border-top: 2px solid #bbb;
            }

            .delivery-progress{
                padding: 12px 15px;
                background: #fff;
                margin-top: 15px;
                color: #999;
                font-size: 12px;
            }

            .delivery-progress .item{
                margin-top: 16px;
            }

            .delivery-progress .red, .delivery-progress .item.is-delivery .delivery-time{
                color: #ff4c29;
            }

            .delivery-progress .icon-right{
                font-size: 12px;
                vertical-align: middle;
                margin-left: 5px;
            }

            .delivery-progress .txt{
                width: 34px;
                height: 34px;
                line-height: 34px;
                border-radius: 50%;
                color: #fff;
                text-align:center;
                display: inline-block;
                background: #ff4c29;
                margin-right: 10px;
                font-size: 14px;
                font-weight: bold;
                box-sizing: border-box;
            }

            .delivery-progress .progress-line{
                position: absolute;
                left: 15px;
                background: #ff4c29;
                width: 4px;
                height: 10px;
            }

            .delivery-progress .static .txt{
                background: #fff;
                border: 3px solid #ccc;
                color: #ccc;
                line-height: 28px;
            }

            .delivery-progress .static .progress-line{
                background: #ccc;
            }

            .delivery-progress .bottom{
                bottom: -10px;
            }

            .delivery-progress .top{
                top: -10px;
            }

            .delivery-progress .delay-btn{
                margin-left: 20px;
                background: #ff4c29;
                color: #fff;
                padding: 4px 8px;
                border-radius: 3px;
            }

            .delivery-progress .desc{
                width: calc(100% - 44px);
                width:-webkit-calc(100% - 44px);
            }

            .delivery-progress .item.is-delayed{
                color: #ccc;
            }

            .delivery-progress .item.is-delayed .txt{
                background: #fff;
                border: 3px dashed #ccc;
                color: #ccc;
                line-height: 28px;
            }

            .delivery-progress .item.is-delayed .delay-btn{
                background: #ccc;
                padding: 4px 14px;
            }

            .delivery-progress .item.is-delayed .progress-line{
                background: #ccc;
            }

            .delivery-progress .item.is-delivery .delay-btn, .delivery-progress .item.static .check-detail, .delivery-progress .item.is-delayed .check-detail,
            .delivery-progress .progress-detail .item:first-child .top, .delivery-progress .progress-detail .item:last-child .bottom{
                display: none;
            }

            .delivery-progress .progress-detail .item:first-child{
                margin-top: 0;
            }

		</style>
	</head>
	<body class="order-page order-detail">

		<div class="count-down bg-cover" style="background-image: url({pigcms{$static_path}villagegroup/images/textBg.jpg);">
			<span class="font_size18">{pigcms{$order.status_txt}</span>
		</div>

		<div class="address-box">
			<div class="mgb10">
				<img src="{pigcms{$order.now_user.avatar}">
                <if condition="!empty($order['now_user']) && $order['now_user']['nickname']">
				    <span>{pigcms{$order.now_user.nickname}</span>
                    <else/>
                    <span>用户已注销</span>
                </if>
			</div>
			<!-- <p>取货地址：湖南省长沙市开福区长沙市开福区营盘西路吉景小区123终点站创福超市</p> -->
            <if condition="!empty($order['now_user']) && $order['now_user']['phone']">
                <a href="tel:{pigcms{$order.now_user.phone}"><p class="mgb5"><i class="icon iconfont" id='icon-phone'>&#xe6dd;</i>联系电话：<font color="blue">{pigcms{$order.now_user.phone}</font></p></a>
            </if>
            <!-- 提货人 -->
            <if condition="$order.pick_name && $order.pick_name">
                <p><i class="icon iconfont" id='icon-location'>&#xe600;</i>提货人：{pigcms{$order.pick_name} <span style="margin-left: 6px;">{pigcms{$order.pick_phone}</span></p>
            </if>
		</div>

		<div class="goodsBox cart">
			<volist name="order.order_detail" id="vo">
			<div class="goodsItem">
				<div class="image">
					<img src="{pigcms{$vo.image}">
					<!-- 增加图标 -->
					<if condition="$vo['label']['type'] eq 1">
						<img class="icon small " src="{pigcms{$vo['label']['image']}">
					</if>
					
						
					<if condition="$vo['label']['type'] eq 0">
						<span class="icon icon-text small " style="background:linear-gradient(to bottom, {pigcms{$vo['label']['color']} , {pigcms{$vo['label']['color2']})">{pigcms{$vo['label']['name']}</span>
					</if>
				</div>
				<div class="info">
					<div class="name">{pigcms{$vo.name}</div>
					
					<div class="font_size12 color_9">团长营业时间：<if condition="$header['start_time'] neq '00:00:00' AND $header['start_time'] neq ''">{pigcms{$header.start_time|substr=0,-3}-{pigcms{$header.end_time|substr=0,-3}<else />08:00-20:00</if></div>
					<div class="font_size12 color_9">

                        <if condition="$order['goods'][$vo['goods_id']]['village_group_type'] eq 1">
                            <div class="tag future">周期购</div>
                        <elseif condition="$order['goods'][$vo['goods_id']]['open_time'] eq 1"/>
							<div class="tag future">预售</div> {pigcms{$order['goods'][$vo['goods_id']]['get_time']|date="m.d",###}送达
						<elseif condition="$order['goods'][$vo['goods_id']]['open_time'] eq 3" />
							<div class="tag">当日达</div>
						<elseif condition="$order['goods'][$vo['goods_id']]['open_time'] eq 2" />
							<div class="tag">次日达</div>
							
						
						</if>
					</div>
					<!--div class="font_size12 color_9">规格：袋</div-->
					<div class="price-box">
<!--						<span class="price">{pigcms{$config['Currency_symbol']}<span class="font_size20">{pigcms{$vo.price}</span></span>-->
<!--						<span class="old-price">{pigcms{$config['Currency_symbol']}{pigcms{$vo.old_price|floatval}</span>-->
<!---->
<!--						<span class=" mgt5">× {pigcms{$vo.num}</span>-->

                        <if condition="!empty($level_off_system) && !empty($good_discount) && !empty($good_discount[$vo['goods_id']])">
                            <span style='    color: #fff; background: #fe3112;padding: 1px 3px; border-radius: 5px; font-size: 12px;'>VIP</span>
                            <span class=" mgt5" style="padding-left:3px">×{pigcms{$vo.num}</span>
                            <span style='    ' class='vipMoney'>{pigcms{$config['Currency_symbol']}{pigcms{$vo.price|floatval}</span>
                            <span style='    font-weight: bold;'>{pigcms{$config['Currency_symbol']}{pigcms{$good_discount[$vo['goods_id']]['discount_price']|floatval}</span>
                            <else/>
                            <span class=" mgt5" style="padding-left:3px">×{pigcms{$vo.num}</span>
                            <if condition="$vo['old_price'] gt $vo['price']">
                            <span style='    ' class='vipMoney'>{pigcms{$config['Currency_symbol']}{pigcms{$vo.old_price|floatval}</span>
                        	</if>
                            <span style='    font-weight: bold;'>{pigcms{$config['Currency_symbol']}{pigcms{$vo.price|floatval}</span>
                            <div>
                            <span class="font_size12 color_9">打包费：{pigcms{$config['Currency_symbol']}{pigcms{$vo.packing_fee|floatval}</span>
                            </div>
                        </if>
					</div>
				</div>
				<span class=" orderStatus" style="bottom: 8px;right: 12px;  padding: 0 5px;  background: #ff6b6b;  color: #fff; border-radius: 4px; position: absolute;">配送状态</span>
				<div class="status_model" style="display:none">
					<section class="g_details">
					    <div style="overflow:hidden">
							<span class="close">
								<img src="{pigcms{$static_path}images/xx_03.png" style="width:20px;"> 
							</span>
						</div>
						<h3>配送状态</h3>
						<div class="orders_list">
							<ul>
								<volist name="vo.goods_log" id="log">
									<li>
										<div class="time">{pigcms{$log['dateline']|date="Y-m-d H:i:s",###}</div>
										<div class="p18">
											<div class="con">
												<!-- <h2>订单生成成功</h2> 0：未配送 1 已配送 2 已送达  3 待自提 4 取消配送', -->
												<if condition="$log['deliver_status'] eq 0">
													<p>已下单，待配送</p>
												<elseif condition="$log['deliver_status'] eq 1"/>
													<p>已配送</p>
                                                <elseif condition="$log['deliver_status'] eq 2 && $order['is_pick_group'] neq 1"/>
                                                    <p>团长收货 待自提</p>
                                                <elseif condition="$log['deliver_status'] eq 2 && $order['is_pick_group'] eq 1"/>
                                                    <p>团长收货 待配送</p>
												<elseif condition="$log['deliver_status'] eq 3"/>
													<p>已取货</p>
                                                <elseif condition="$log['deliver_status'] eq 5"/>
                                                    <p>已评价</p>
                                                <elseif condition="$log['deliver_status'] eq 6"/>
                                                    <p>配送员接单</p>
                                                <elseif condition="$log['deliver_status'] eq 7"/>
                                                    <p>团长处取货</p>
                                                <elseif condition="$log['deliver_status'] eq 8"/>
                                                    <p>配送员配送</p>
                                                <elseif condition="$log['deliver_status'] eq 9"/>
                                                    <p>配送完成</p>
                                                </if>
											</div>
										</div>
									</li>
								</volist>
							</ul>
						</div>
					</section>
				</div>
			</div>
		</volist>

            <if condition="$system_coupon">
                <div class="line_discount more more-weak">
                    <span>平台优惠券</span>
                    <span class="more-after fr"  style="color:red;">
                    <php>if($system_coupon){
                            if($system_coupon['order_money']>0){
                    </php>
                                满{pigcms{$system_coupon.order_money}
                        <php>}</php>
                        <php>if($system_coupon['is_discount']==1){</php>
                                打{pigcms{$system_coupon.discount_value|floatval}折
                        <php>}else{</php>
                                减{pigcms{$system_coupon.discount}
                        <php>}</php>
                    <php> }else{ </php>
                       使用优惠券
                    <php> } </php>
                </span>
                </div>
            </if>
			

			

		</div>

		<div class="other-info">
			<p>{pigcms{$order.real_orderid}</p>
			<p>下单时间：{pigcms{$order.add_time|date='Y-m-d H:i',###}</p>
			<if condition="$order.get_start_time neq '00:00:00' && $order.get_end_time neq '00:00:00'">
				<p>取货时间：{pigcms{$order.get_start_time} - {pigcms{$order.get_end_time}</p>
			</if>
			<if condition="$order.pay_time gt 0">
				<p>支付时间：{pigcms{$order.pay_time|date='Y-m-d H:i',###}</p>
				<p>支付方式：{pigcms{$order.pay_type_txt}</p>
			</if>
			
			<if condition="$order.status eq 2">
				<p>完成时间：{pigcms{$order.bill_time|date='Y-m-d H:i',###}</p>
			</if>
            <if condition="$order.vip_discount_money gt 0">
                <p>等级优惠金额：{pigcms{$order.vip_discount_money}</p>
            </if>
            <if condition="$order.discount_price gt 0">
                <p>优惠后金额：{pigcms{$order.discount_price}</p>
            </if>
            <if condition="$order.score_used_count gt 0">
                <p>使用积分：{pigcms{$order.score_used_count}</p>
            </if>
            <if condition="$order.score_deducte gt 0">
                <p>积分抵扣金额：{pigcms{$order.score_deducte}</p>
            </if>
            <if condition="$order.delivery_fee gt 0">
                <p>配送费：{pigcms{$order.delivery_fee}</p>
            </if>
		</div>



        <if condition="$order['village_group_type'] eq 1 && $order['status'] gt 0 && $order['status'] neq 10">
        <div class="delivery-progress">
            <div class="flex flex_justify flex-align font_size14 mgb10">
                <span class="red">配送日期</span>
                <div><span class="red">{pigcms{$cycle_order_count}</span> / {pigcms{$order.delivery_periods}</div>
            </div>

            <div class="progress-detail">
                <volist name="cycle_order_detail" id="item">
                    <div class="item flex <if condition="$item['is_delay'] eq 1">is-delayed<elseif condition="$item['status'] gt 0 && $item['status'] neq 6"/>is-delivery<else/>static</if>">
                    <div class="rel">
                        <span class="progress-line top"></span>
                        <span class="txt">
                                <if condition="$item['is_delay'] eq 1">-<else/>{pigcms{$item['date_num']}</if>
                            </span>
                        <span class="progress-line bottom"></span>
                    </div>
                    <div class="flex flex_justify desc">
                        <div class="delivery-time">
                            <div>
                                <if condition="$item['status'] gt 0 && $item['status'] neq 6 && $item['is_delay'] neq 1">
                                    已配送
                                    <if condition="$item['real_send_time'] && $item['real_send_time'] lt $item['send_time']">
                                        （提前配送）
                                    </if>
                                    <elseif condition="$item['is_delay'] neq 1 && $item['status'] eq 0"/>
                                    待配送
                                    <elseif condition="$item['status'] eq 6"/>
                                    已延期
                                </if>
                            </div>
                            <div>{pigcms{$item['send_time']|date='Y-m-d',###}</div>
                        </div>
                        <if condition="($item['is_delay'] neq 1 && !$no_delay) || $item['is_delay'] eq 1">
                            <div class="delay-btn">
                                <if condition="$item['is_delay'] neq 1">
                                    <span class="delay_submit" data-detail_id="{pigcms{$item['id']}">点击延期</span>
                                    <elseif condition="$item['delay_user'] eq 0"/>
                                    已延期
                                    <elseif condition="$item['delay_user'] eq 1"/>
                                    团长已延期
                                    <elseif condition="$item['delay_user'] eq 2"/>
                                    管理员已延期
                                    <elseif condition="$item['delay_user'] eq 3"/>
                                    商家已延期
                                </if>
                            </div>
                        </if>
                        <div class='check-detail'>
                            <if condition="$item['real_send_time']">
                                <div>{pigcms{$item['real_send_time']|date='Y-m-d H:i:s',###}</div>
                            </if>
                            <div class="orderStatus red" data-goods_id="{pigcms{$vo.goods_id}">查看配送详情<i class="icon iconfont icon-right">&#xe603;</i></div>
                            <div class="status_model" style="display:none" data-id="{pigcms{$vo['id']}">
                                <section class="g_details">
                                    <div style="overflow:hidden">
										<span class="close">
											<img src="{pigcms{$static_path}images/xx_03.png" style="width:20px;"> 
										</span>
									</div>
									<h3>配送状态</h3>
                                    <div class="orders_list">
                                        <ul>
                                            <volist name="goods_all_log[$item['id']]" id="log">
                                                <li>
                                                    <div class="time">{pigcms{$log['dateline']|date="Y-m-d H:i:s",###}</div>
                                                    <div class="p18">
                                                        <div class="con">
                                                            <!-- <h2>订单生成成功</h2> 0：未配送 1 已配送 2 已送达  3 待自提 4 取消配送', -->
                                                            <if condition="$log['deliver_status'] eq 0">
                                                                <p>未配送</p>
                                                                <elseif condition="$log['deliver_status'] eq 1"/>
                                                                <p>已配送</p>
                                                                <elseif condition="$log['deliver_status'] eq 2"/>
                                                                <p>团长收货 待自提</p>
                                                                <elseif condition="$log['deliver_status'] eq 3"/>
                                                                <p>已取货</p>
                                                                <elseif condition="$log['deliver_status'] eq 5"/>
                                                                <p>已评价</p>
                                                            </if>
                                                        </div>
                                                    </div>
                                                </li>
                                            </volist>
                                        </ul>
                                    </div>
                                </section>
                            </div>

                        </div>

                    </div>
            </div>
            </volist>

        </div>
        </if>


        </div>


		<div class="total-box">
			<span>实付金额：<span class="red">{pigcms{$config['Currency_symbol']}</span><span class="red font_size18 font_b">{pigcms{$order.actual_discount_money|floatval}</span></span>
		
			<div class="fr">
			<if condition="$order['status'] eq 1">
					<!--div class="btn unfinish ">提醒发货</div-->
			<elseif condition="$order['status'] eq 2" />
				<div class="btn finish">已完成</div>
			</if>
			</div>
		</div>



		<div id='mask-wrapper' class="hidden">
			<div class="mask"></div>
			<div class="mask-content">
				<a href="tel:{pigcms{$config.site_phone}"><div class="phone-box"><i class="icon iconfont mgr5 icon-phone">&#xe6dd;</i>{pigcms{$config.site_phone}</div></a>
				<span class="icon iconfont icon-cancel">&#xe679;</span>
			</div>
		</div>

		<div class="coupon_mask cursor" style="display:none"></div>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
		<script type="text/javascript">
			$(function(){
				$(document).on('click', '.unfinish', function(){
					$('#mask-wrapper').removeClass('hidden');
				});

				$(document).on('click', '.icon-cancel, .mask', function(){
					$('#mask-wrapper').addClass('hidden');
					return false;
				});
				$(".orderStatus").on("click",function(e){
					e.stopPropagation();
					e.preventDefault();
					$(this).next().show();
					$(".coupon_mask").show()
				})
				$(".close").on("click",function(e){
					e.stopPropagation();
					e.preventDefault();
					$(".coupon_mask,.status_model").hide()
				})
				$('.coupon_mask').bind("touchmove",function(e){
					e.preventDefault();
				});

                $('.delay_submit').click(function () {
                    var detail_id = $(this).data('detail_id');
                    layer.open({
                        title:['提示','background-color:#FF658E;color:#fff;'],
                        content:'<p style="width:180px;color:red;">确认延期该周期购吗？延期后不可撤销！</p>',
                        btn: ['确定','取消'],
                        yes: function(index){
                            layer.close(index);
                            $.post("{pigcms{:U('village_group_delay')}",{id:detail_id,is_header:1},function(data){
                                console.log('data',data);
                                if(data.status==1){
                                    layer.open({
                                        content: data.info
                                        ,skin: 'msg'
                                        ,anim: 'up'
                                        ,time: 2
                                    });
                                    window.location.reload();
                                }else{
                                    layer.open({
                                        content: data.info
                                        ,skin: 'msg'
                                        ,anim: 'up'
                                        ,time: 2
                                    });
                                }
                            },'json')
                        }
                    });
                });
			})
		</script>
	</body>
</html>