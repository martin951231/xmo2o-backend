<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>订单确认</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=yes"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/index.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/orderDetail.css?t={pigcms{:time()}"/>
	</head>
	<body class="order-page order-detail">

		<div class="count-down bg-cover" style="background-image: url({pigcms{$static_path}villagegroup/images/textBg.jpg);">
			<if condition="$order.status eq 0">
			<span class="font_size18">待付款</span>
			<if condition="$order['status'] eq 0 AND $order['add_time'] GT 0 AND $order['add_time']+$unpaid_timeout_seconds gt time()"><span class="font_size12" >(剩余<em id="time"></em>)</span></if>
			<elseif condition="$order.status eq 1" />
			<span class="font_size18">已支付，未发货</span>
			<elseif condition="$order.status eq 2" />
			<span class="font_size18">已消费</span>
			<elseif condition="$order.status eq 3" />
			<span class="font_size18">部分消费</span>
			
			<elseif condition="$order.status eq 5" />
			<span class="font_size18">已发货</span>
            <elseif condition="$order.status eq 6 && $order.is_pick_group neq 1" />
                <span class="font_size18">团长已收货，待自提</span>
            <elseif condition="$order.status eq 6 && $order.is_pick_group eq 1"/>
                <span class="font_size18">团长已收货，待配送</span>
			<elseif condition="$order.status eq 7" />
			<span class="font_size18">{pigcms{$order.status_txt}</span>
			<elseif condition="$order.status eq 8" />
			<span class="font_size18">{pigcms{$order.status_txt}</span>
			<elseif condition="$order.status eq 10" />
			<span class="font_size18" >超时未支付，请重新选购</span>
				<elseif condition="$order.status eq 12 || $order.status eq 4" />
				<span class="font_size18">已退款</span>
			<elseif condition="$order.status eq 11" />
			<span class="font_size18">{pigcms{$order.status_txt}</span>
			<elseif condition="$order.status eq 13" />
			<span class="font_size18">已评价</span>
			</if>
		</div>

		<div class="address-box">
			<div class="mgb5">
				<span class="icon">团长</span>
				<span>{pigcms{$header.name}</span>
				<span class="font_size12 color_9 mgl5">{pigcms{$header.pick_address.name}</span>
			</div>
			<!-- 增加电话 -->
            <if condition="!$config['village_group_hide_detail']">
			    <a href="tel:{pigcms{$header.phone}"><p class="mgb5"><i class="icon iconfont" id='icon-phone'>&#xe6dd;</i>联系电话：<font color="blue">{pigcms{$header.phone}</font></p></a>
            </if>
			<p><i class="icon iconfont" id='icon-location'>&#xe60b;</i>取货地址：<if condition="$order['pick_addr'] neq ''">{pigcms{$order['pick_addr']}<else/>{pigcms{$header.pick_address.pick_addr}</if></p>
            <!-- 提货人 -->
            <if condition="$order.pick_name && $order.pick_name">
                <p><i class="icon iconfont" id='icon-location'>&#xe600;</i>提货人：{pigcms{$order.pick_name} <span style="margin-left: 6px;">{pigcms{$order.pick_phone}</span></p>
            </if>
		</div>

		<div class="goodsBox cart">
		<volist name="order.order_detail" id="vo">

			<div class="goodsItem" style="position:relative">
				<a href="{pigcms{:U('Village_group/goods',array('goods_id'=>$vo['goods_id'],'from_index'=>1,'leader_id'=>$header['id'], 'from_order'=>1))}">
				<div class="image">
					<img src="{pigcms{$vo.image|default='/static/images/empty.png'}"/>
					<!-- 增加图标 -->
					<if condition="$vo['label']['type'] eq 1">
						<img class="icon small " src="{pigcms{$vo['label']['image']}">
					</if>
					
					<if condition="$vo['label']['type'] eq 0">
						<span class="icon icon-text small " style="background:linear-gradient(to bottom, {pigcms{$vo['label']['color']} , {pigcms{$vo['label']['color2']})">{pigcms{$vo['label']['name']}</span>
					</if>
				</div>
				<div class="info">
					<div style='    display: flex;'>
						<div class="name" style='flex:1'>{pigcms{$vo.name}</div>

					</div>

					<div class="font_size12 color_9">团长营业时间：<if condition="$header.start_time neq '00:00:00'">{pigcms{$header.start_time|substr=0,-3}-{pigcms{$header.end_time|substr=0,-3}<else />08:00-20:00</if></div>
					<div class="font_size12 color_9">

                        <if condition="$order['goods'][$vo['goods_id']]['village_group_type'] eq 1">
                            <div class="tag future">周期购</div>
						<elseif condition="$order['goods'][$vo['goods_id']]['open_time'] eq 1"/>
							<if condition="$order['goods'][$vo['goods_id']]['cat_show_tag'] eq 1">
							<div class="tag future">预售</div>
							</if>
							{pigcms{$order['goods'][$vo['goods_id']]['get_time']|date="m.d",###}送达
						<elseif condition="$order['goods'][$vo['goods_id']]['open_time'] eq 3" />
							<div class="tag">当日达</div>
						<elseif condition="$order['goods'][$vo['goods_id']]['open_time'] eq 2" />
							<div class="tag">次日达</div>
						</if>
					</div>
					
					<div class="price-box">
						<span>

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
                                 <span class="font_size12 color_9">打包费：{pigcms{$config['Currency_symbol']}{pigcms{$vo.packing_fee|floatval}</span>
                            </if>
                        </span>
						<!--<span class="price">{pigcms{$config['Currency_symbol']}<span class="font_size20">{pigcms{$vo.price}</span></span>
                        <if condition="$vo['old_price'] gt $vo['price']">
                            <span class="old-price">{pigcms{$config['Currency_symbol']}{pigcms{$vo.old_price|floatval}</span>
                        </if>-->


					</div>
				</div>
				</a>
                
				<div class="status_model status_mask" style="display:none">
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
			<p style="position: relative;">订单号：{pigcms{$order.real_orderid}
				<if condition="$vo['village_group_type'] neq 1 && $order['status'] gt 0 && $order['status'] neq 10 && $order['status'] neq 11 && $order['is_pick_group'] neq 0">
                    <span class=" orderStatus" style="bottom: 0px;right: 0px;  padding: 0 5px;  background: #ff6b6b;  color: #fff; border-radius: 4px; position: absolute;" data-goods_id="{pigcms{$vo.goods_id}">配送状态</span>
                </if>
			</p>
			<if condition="$order.get_start_time neq '00:00:00' && $order.get_end_time neq '00:00:00'">
				<p>取货时间：{pigcms{$order.get_start_time} - {pigcms{$order.get_end_time}</p>
			</if>
			<p>下单时间：{pigcms{$order.add_time|date='Y-m-d H:i',###}</p>
			<if condition="$order.pay_time gt 0">
				<p>支付时间：{pigcms{$order.pay_time|date='Y-m-d H:i',###}</p>
				<p>支付方式：{pigcms{$order.pay_type_txt}</p>
			</if>
			<if condition="$order.note">
				<p style="word-break: break-all;">订单备注：{pigcms{$order.note}</p>
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

        <if condition="$order['village_group_type'] eq 1 && $order['status'] gt 0 && $order['status'] neq 10 && $order['status'] neq 11 && $order['status'] neq 12">
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
                                        <span class="delay_submit" data-detail_id="{pigcms{$item['id']}">我要延期</span>
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
                                <elseif condition="$item['status'] lt 1 && $no_delay"/>
                                <div>
                                    <span class="delay_gray">我要延期</span>
                                </div>
                            </if>
                            <div class='check-detail'>
                                <if condition="$item['real_send_time']">
                                    <div>{pigcms{$item['real_send_time']|date='Y-m-d H:i:s',###}</div>
                                </if>
                                <div class=" orderStatusClick red" data-goods_id="{pigcms{$vo.goods_id}" data-id="{pigcms{$item['id']}">查看配送详情<i class="icon iconfont icon-right">&#xe603;</i></div>
                                <div class="status_model status_modelClick status_model_msg_{pigcms{$item.id}" style="display:none" data-id="{pigcms{$vo['id']}">
                                    <section class="g_details">
                                       <div style="overflow:hidden">
                                            <span class="close">x</span>
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
			

		</div>
        </if>


		<div class="total-box">
			<span>实付款：<span class="red">{pigcms{$config['Currency_symbol']}</span><span class="red font_size18 font_b">{pigcms{$order.actual_discount_money_info|floatval}</span></span>
			<div class="fr" <if condition="$order.status eq 0">style="width:183px;"</if>>
			<if condition="$order.status eq 0">
				<a href="{pigcms{:U('cancelOrder',array('order_id'=>$order['order_id'],'leader_id'=>$order['start_uid']))}"><div class="back">取消订单</div></a>
				<a href="/packapp/community_group/index.html#/confirm?leader_id={pigcms{$order['start_uid']}&order_id={pigcms{$order['order_id']}"><div class="btn" style="background:#ff4c29;" >继续支付</div></a></if>

			<if condition="$order.status eq 5"><a href="{pigcms{:U('index')}"><div class="btn" style="background:#ff4c29;" >继续选购</div></a></if>
			</div>
            <!--//已支付取消订单===>郭先森-->
			<if condition="$village_group_unshipped_cancel_order eq 1">
				<div class="fr">
					<!--<if condition="$order.status eq 5">
						<php>if($order['village_group_type'] != '1'){</php>
						<a href="javascript:;"><div class="back btn confirmGet" data-oid="{pigcms{$order['order_id']}" style="background:#ff4c29;">确认收货</div></a>
						<php>}</php>
                    </if>-->
					<if condition="$order.status eq 1">
						<if condition="$order['village_group_type'] != '1'">						
						<a href="javascript:;" onclick="window.location.href='{pigcms{:U('Village_group/group_order_refund',array('order_id'=>$order['order_id']))}'"><div class="back" style="border: 1px solid #eaeaea;line-height: 43px;">取消订单</div></a>
					</if>
				</div>
			</if>
			<if condition="$village_group_consumed_cancel_order eq 1">
				<div class="fr">
					<if condition="in_array($order['status'], [2,13])">
						<a href="javascript:;" onclick="window.location.href='{pigcms{:U('Village_group/refund',array('order_id'=>$order['order_id']))}'"><div class="back" style="border: 1px solid #eaeaea;line-height: 43px;">申请售后</div></a>
					</if>
				</div>
			</if>
		</div>

	</body>

	<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
	<div class="coupon_mask cursor" style="display:none"></div>
	<!-- layer -->
    <script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
	<script type="text/javascript">
		$(".orderStatus").on("click",function(e){
			e.stopPropagation();
			e.preventDefault();
			$(this).next().show();
			$(".coupon_mask, .status_model.status_mask").show()

		})

		$(".orderStatusClick").on("click",function(e){
			e.stopPropagation();
			e.preventDefault();
			var id = $(this).data('id');
			console.log('goods_id',id)
			$('.status_model_msg_'+id).show();
			$(".coupon_mask, .status_model_msg_"+id).show()

		})

		$(".close").on("click",function(e){
			e.stopPropagation();
			e.preventDefault();
			$(".coupon_mask,.status_model").hide()
		})
		var addTimer = function(){
			var list = [],
			  interval;
			  
			return function(id,timeStamp){
			  if(!interval){
				interval = setInterval(go,1);
			  }
			  list.push({ele:document.getElementById(id),time:timeStamp});
			}

			function go() { 
			  for (var i = 0; i < list.length; i++) { 
				list[i].ele.innerHTML = changeTimeStamp(list[i].time); 
				if (!list[i].time) 
				  list.splice(i--, 1); 
			  } 
			}

			function changeTimeStamp(timeStamp){
			  var distancetime = new Date(timeStamp*1000).getTime() - new Date().getTime();
			  if(distancetime > 0){ 
			   // var ms = Math.floor(distancetime%1000);
				var sec = Math.floor(distancetime/1000%60);
				var min = Math.floor(distancetime/1000/60%60);
				var hour =Math.floor(distancetime/1000/60/60%24);
				var day =Math.floor(distancetime/1000/60/60/24);
				var day_txt  = '';
				if(day>=1){
					day_txt = day+'天 ';
				}
				if(sec<10){
				  sec = "0"+ sec;
				}
				if(min<10){
				  min = "0"+ min;
				}
				if(hour<10){
				  hour = "0"+ hour;
				}
				if(hour==0){
				  hour = "";
				}
				
				return day_txt+hour + ":" +min + ":" +sec ;
			  }else{
				clearInterval(interval);
				window.location.reload();
			  }  
			}
		}();		
	
		$(function(){
			<if condition="$order['status'] eq 0 AND $order['add_time'] GT 0 AND $order['add_time']+$unpaid_timeout_seconds gt time()">
				addTimer('time',"{pigcms{$order['add_time']+$unpaid_timeout_seconds}");
			</if>
		});


        $('.delay_submit').click(function () {
            var detail_id = $(this).data('detail_id');
            layer.open({
                title:['提示','background-color:#FF658E;color:#fff;'],
                content:'<p style="width:180px;color:red;">确认延期该周期购吗？延期后不可撤销！</p>',
                btn: ['确定','取消'],
                yes: function(index){
                    layer.close(index);
                    $.post("{pigcms{:U('village_group_delay')}",{id:detail_id},function(data){
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

        $(".confirmGet").click(function(){
			var order_id = $(this).data('oid');
			layer.open({
                title:['提示','background-color:#FF658E;color:#fff;'],
                content:'<p style="width:180px;color:red;">请收到货后点击完成按钮，是否确定？</p>',
                btn: ['确定','取消'],
                yes: function(index){
                    layer.close(index);
                    $.post("{pigcms{:U('ajax_verify')}",{order_id:order_id,confirm:1},function(data){
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

		window.addEventListener("pageshow",function(event){
            var lastPageNeedReload= sessionStorage.getItem('lastPageNeedReload');
            if(lastPageNeedReload){
                sessionStorage.removeItem('lastPageNeedReload');
                location.reload();
            }
        },false);
	</script>
</html>