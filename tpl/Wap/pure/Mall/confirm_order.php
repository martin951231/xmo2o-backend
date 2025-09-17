<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}shop/css/datePicker.css">
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}shop/css/mobiscroll_min.css" media="all">
	<script type="text/javascript" src="{pigcms{$static_path}shop/js/jquery1.8.3.js"></script>
	<script type="text/javascript" src="{pigcms{$static_path}shop/js/dialog.js"></script>
	<script type="text/javascript" src="{pigcms{$static_path}shop/js/mobiscroll_min.js"></script>

	<title>{pigcms{$store['name']|default="商城"}</title>
	<meta content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<meta name="Keywords" content="">
	<meta name="Description" content="">
	<meta content="telephone=no, address=no" name="format-detection">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="format-detection" content="telephone=no"/>
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}shop/css/main.css" media="all">
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}mall/css/index.css" media="all">
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}mall/css/confirm_order.css" media="all">
	<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210cc" charset="utf-8"></script>
	 
	<style>
		.box li a{ margin-bottom: 10px;}
		.box li a.btn_express{
			padding: 0 8px;
		}
		.pre_sale_info{
			width:100%;
			padding:10px 0;
			box-sizing:border-box;
			display: flex;
			flex-direction: column;
			background:#ffffff;
		}
		.pre_sale_info .keyvaluebox{
			width: 100%;
			display:flex;
			align-items: center;
			justify-content:space-between;
			padding: 0 15px;
			box-sizing:border-box;
		}
		.pre_sale_info .keyvaluebox .kebox{
			height:20px;
			font-size:14px;
		}
		.pre_sale_info .keyvaluebox .valuebox{
			height:20px;
			font-size:13px;
		}
		.fixed_tips{
			position:fixed;
			width: 100%;
			height: 30px;
			background-color: #FFF6E0;
			color: #FF9743;
			line-height: 30px;
			box-sizing: border-box;
			padding: 0 10px;
			bottom: 50px;
			font-size:13px;
			z-index: 10;
		}
	</style>
</head>
<script type="text/javascript" src="{pigcms{$static_path}shop/js/scroller.js"></script>
<body onselectstart="return true;" ondragstart="return false;">
<div class="container">
    <if condition="$onlybuy">
        <form name="cart_confirm_form" id="cart_confirm_form" action="{pigcms{:U('Mall/save_order',array('store_id'=> $store['store_id'], 'mer_id' => $store['mer_id'], 'village_id'=>$village_id,'onlybuy'=>$onlybuy,'group_id'=>$group_id,'bar_id'=>$bar_id,'team_id'=>$team_id))}" method="post">
        <else />
        <form name="cart_confirm_form" id="cart_confirm_form" action="{pigcms{:U('Mall/save_order',array('store_id'=> $store['store_id'], 'mer_id' => $store['mer_id'], 'village_id'=>$village_id))}" method="post">
    </if>
    
    <input name="openid" type="hidden" value="{pigcms{$_GET['openid']}">
    <input name="share_uid" type="hidden" value="{pigcms{$_GET['share_uid']}">
	<section class="menu_wrap pay_wrap">
		<ul class="box">
			<li class="curWay">
				<a class="">配送方式：</a>&nbsp;&nbsp;
				<if condition="in_array($delivery_type, array(3,5,7,8,-1))">
					<a class="btn_express
						<if condition="$pick_addr_id"> pick_in_store_click
						<elseif condition="$_GET['deliver_key'] eq 3" />pick_in_store_click
						<else />pick_in_store
						</if>" id="post_package" data-key='1'>快递配送
					</a>&nbsp;&nbsp;
				</if>
				<if condition="in_array($delivery_type, array(2, 3, 4,6,8,-1))">
					<a class="btn_express
						<if condition="$pick_addr_id">pick_in_store
							<elseif condition="$delivery_type neq 2" />
							pick_in_store_click
							<else />
							pick_in_store
						</if>" id="pick_in_store" data-key='2'>自提
					</a>&nbsp;&nbsp;
				</if>
				<if condition="in_array($delivery_type, array(0,6,7,8)) && C('config.open_mall_deliver')">
					<a class="btn_express
					<if condition="$pick_addr_id">pick_in_store_click
					<elseif condition="$_GET['deliver_key'] eq 3" />pick_in_store
					<elseif condition="$delivery_type neq 2" />pick_in_store_click
					<else />pick_in_store
					</if>" id="post_plat" data-key='3'>{pigcms{$config.deliver_name}
				</a>
				</if>
			</li>
			<div class="tipSS" style="text-align: center;"> </div>
			<div class="tipSS1" style="text-align: center;">  </div>
			<if condition="$delivery_type neq 2">
			<li id="li_delivery" <if condition="$pick_addr_id">style="display:none"</if>>
				<a class="a-link" data-href="{pigcms{:U('My/adress',array('buy_type' => 'mall', 'store_id'=>$store['store_id'], 'village_id'=>$village_id, 'mer_id' => $store['mer_id'], 'current_id'=>$user_adress['adress_id'],'params'=>$_GET['params'],'order_id'=>$order_id,'group_id'=>$group_id,'bar_id'=>$bar_id,'team_id'=>$team_id))}">
					<strong>
						<span id="showAddres">
							<if condition="$show_user_adress['adress_id']">{pigcms{$show_user_adress['show_address']}
								<else/>
								请点击添加送货地址
							</if>
						</span>
						<span id="showName">{pigcms{$show_user_adress['name']}</span>
						<span id="showTel">{pigcms{$show_user_adress['phone']}</span>
					</strong>
					<div><i class="ico_arrow"></i></div>
				</a>
			</li>
			</if>
			<if condition="in_array($delivery_type, array(2, 3, 4,6,8))">
			<li id="li_pick" <if condition="$delivery_type neq 2 AND empty($pick_addr_id)">style="display:none"</if>>

				<if condition="$pick_address_count gt 1">
					<a class="a-link" data-href="{pigcms{:U('My/pick_address',array('buy_type' => 'mall', 'store_id'=>$store['store_id'], 'village_id' => $village_id, 'mer_id' => $store['mer_id'],'pick_addr_id' => $pick_address['pick_addr_id'],'params'=>$_GET['params'],'order_id'=>$order_id,'group_id'=>$group_id,'bar_id'=>$bar_id,'team_id'=>$team_id),true,false,true)}">
						<strong>
							<span id="showAddres">地址：{pigcms{$pick_address['name']}</span><br>
							<span id="showName">电话：{pigcms{$pick_address['phone']}</span><br>
							<span id="showTel">省市区：{pigcms{$pick_address['area_info']['province']} {pigcms{$pick_address['area_info']['city']} {pigcms{$pick_address['area_info']['area']}</span>
						</strong>
						<div><i class="ico_arrow"></i></div>
					</a>
				<else/>
					<a class="" href="javascript:void(0)">
						<strong>
							<span id="showAddres">地址：{pigcms{$pick_address['name']}</span><br>
							<span id="showName">电话：{pigcms{$pick_address['phone']}</span><br>
							<span id="showTel">省市区：{pigcms{$pick_address['area_info']['province']} {pigcms{$pick_address['area_info']['city']} {pigcms{$pick_address['area_info']['area']}</span>
						</strong>
					</a>
				</if>
			</li>
			</if>
            <if condition="$error_tips">
                <span id="error_tips" style="color: red;font-size: 12px;display: none;">缺货提示：{pigcms{$error_tips}</span>
            </if>
			
            <div><img class="w100" style="display: block;" src="https://hf.pigcms.com/tpl/Wap/pure/static/shop/images/wxapp_addaddress.png"></div>
		</ul>
        <!-- 京东发票 -->
        <if condition="$jd_on eq 1">
            <ul class = "box">
                <!--                <li> 京东发票：</li>-->
                <!--                <li>-->
                <!--                    发票种类：-->
                <!--                    <input type="radio" class="invoiceType" name="invoiceType" value="2">增值税发票&nbsp;-->
                <!--                    <input type="radio" class="invoiceType" name="invoiceType" value="3" checked="checked">电子发票-->
                <!--                </li>-->
                <!--                <li>-->
                <!--                    发票类型：-->
                <!--                    <input type="radio" class="selectedInvoiceTitle" name="selectedInvoiceTitle" value="4" checked="checked">个人&nbsp;-->
                <!--                    <input type="radio" class="selectedInvoiceTitle" name="selectedInvoiceTitle" value="5">单位-->
                <!--                </li>-->
                <!--                <li class="hidden companyName">-->
                <!--                    发票抬头：-->
                <!--                    <input type="text" id="companyName" name="companyName" value="" style="width: 65%;border: 1px solid #ddd;">-->
                <!--                </li>-->
                <!--                <li class="hidden regCode">-->
                <!--                    纳税人识别号：-->
                <!--                    <input type="text" id="regCode" name="regCode" value="" style="width: 58%;border: 1px solid #ddd;">-->
                <!--                </li>-->
                <!--                <li>-->
                <!--                    发票明细：-->
                <!--                    <input type="radio" class="invoiceContent" name="invoiceContent" value="1" checked="checked">明细-->
                <!--                    <input type="radio" class="invoiceContent" name="invoiceContent" value="3">电脑配件-->
                <!--                    <input type="radio" class="invoiceContent" name="invoiceContent" value="19">耗材-->
                <!--                    <input type="radio" class="invoiceContent" name="invoiceContent" value="22">办公用品-->
                <!--                </li>-->
                <li>
                    请选择街道/乡镇：
                    <if condition="count($towns) gt 0">
                    <volist name="towns" id="town">
                        <input type="radio" name="town" value="{pigcms{$town.townId}">{pigcms{$town.townName}&nbsp;&nbsp;
                    </volist>
                    <else />
                        <span style="color: #666;" id="carea">没有下级地址</span>
                    </if>
                </li>
<!--                <li>-->
<!--                    常用邮箱：-->
<!--                    <input type="text" id="email" name="email" value="" style="width: 65%;border: 1px solid #ddd;">-->
<!--                </li>-->
            </ul>
        </if>
        <!-- end -->


		<ul class="box " <if condition="$store['is_invoice'] AND $store['invoice_price'] elt $price">style="display:block"<else/>style="display:none"</if>>
			<li>
				<a href="javascript:void(0);" id="invoiceBtn" class="line_box">
					<strong id="invoiceTxt">点击添加发票抬头</strong>
					<span ></span>
					<div><i class="ico_arrow"></i></div>
				</a>
			</li>
		</ul>
		<if condition="$cue_field">
			<ul class="box pay_box">
				<volist name="cue_field" id="vo">
					<if condition="$vo['type'] eq 1">
						<li>
							<a href="javascript:void(0);" id="cue_field_btn_{pigcms{$key}" class="line_box">
								<strong>{pigcms{$vo.name}</strong>
								<span id="cue_field_{pigcms{$key}_txt">点击填写{pigcms{$vo.name}</span>
								<div><i class="ico_arrow"></i></div>
							</a>
						</li>
					<elseif condition="$vo['type'] eq 2" />
						<li class="cue_field">
	                        <a href="javascript:void(0);" class="line_box">
								<strong>{pigcms{$vo.name}</strong>
		                        <span id="cue_field_txt_{pigcms{$key}">请选择</span>
		                        <div><i class="ico_arrow"></i></div>
	                        </a>
							<php> $ii = $key;</php>
							<input type="hidden" name="cue_field[{pigcms{$key}][title]" value="{pigcms{$vo.name}" />
							<input type="hidden" name="cue_field[{pigcms{$key}][txt]" value="" id="cue_field_{pigcms{$key}_head" class="cue_field_txt_{pigcms{$key}"/>

	                        <div class="mask hidden" data-index="{pigcms{$key}">
	                            <div class="address_tanceng">
	                                <ul>
	                                <php>if (!$vo['iswrite']) {</php>
	                                <li data-s="0">请选择</li>
	                                <php>}</php>
	                                <volist name="vo['use_field']" id="radio">
	                                    <li>{pigcms{$radio}</li>
	                                </volist>
	                                </ul>
	                            </div>
	                        </div>
						</li>
					<!-- <ul class="box pay_box markbox">
						<li class="show_address">
							<a href="javascript:void(0);">
								<strong>地方</strong>
								<span id="address_text">点击添加订单备注</span>
								<div><i class="ico_arrow"></i></div>
							</a>
						</li>
					</ul> -->
					</if>
				</volist>
			</ul>
		</if>
		<div class="store-info">
			<!-- <img src="{pigcms{$static_path}mall/images/seckill.png"> -->
			<span class="name">{pigcms{$store['name']|default="商城"}</span>
		</div>
		<if condition="!empty($goods)">
			<ul class="menu_list order_list" id="orderList">
				<volist name="goods" id="ditem">
					<li class="goods-item" style="margin-bottom: 0px;">
						<div>
						<if condition="!empty($ditem['image'])">
							<img src="{pigcms{$ditem['image']}" alt="">
						</if>
						</div>
						<div>
							<h3 class="ellipsis">{pigcms{$ditem['name']}</h3>
							<div class="spec ellipsis font_size12 color_9" style="line-height: 1.4;">
								<span>{pigcms{$ditem['str']}</span>
							</div>
							<span class="count">{pigcms{$ditem['num']}</span>
							<strong class="price font_size16">
								{pigcms{$config['Currency_symbol']}
								<span class="unit_price">
									{pigcms{$ditem['price']}
									<if condition="$config.open_extra_price eq 1 AND $ditem.extra_price gt 0">
										+{pigcms{$ditem.extra_price}{pigcms{$config.extra_price_alias_name}
									</if>
									<if condition="($ditem['price_type']==3 || $ditem['price_type']==2) && $ditem['account_score'] gt 0">
										+{pigcms{$ditem['account_score']|floatval}{pigcms{$config['score_name']}
									</if>
								</span>
								<!-- 秒杀价 -->
								<if condition="$ditem['is_seckill_price'] && $ditem['discount_price']!=$ditem['price']">
									<span class='seckill_tag'>{pigcms{$config.seckill_alias_name|default='限时优惠'}价</span>
								<elseif condition="$group_id gt 0 && $team_id eq 0 && $ditem['price'] lt $ditem['cost_price']" />
									<span class='seckill_tag'>团长价</span>
								</if>
								<span class="fr font_size14 color_3" style="margin-top: 2px;">x{pigcms{$ditem['num']}</span>
							</strong>
						</div>
					</li>
					<if condition="$ditem.mall_full_layer != []">
						<li style="background: #f8f8f8;margin-bottom: 10px;">
							<div>
								<h3>赠品：</h3>
								<p style="margin-left:15%;" class=''>
									商品:&nbsp;{pigcms{$ditem.mall_full_layer.shop_good_name} &nbsp;{pigcms{$config["Currency_symbol"]}{pigcms{$ditem.mall_full_layer.price}&nbsp;X1
								</p>
                                <if condition="$ditem['mall_full_layer']['coupon_name'] !=''">
                                    <p style="margin-left:15%;" class=''>
                                        优惠券:&nbsp;{pigcms{$ditem.mall_full_layer.coupon_name} &nbsp;X1
                                    </p>
                                </if>
							</div>
						</li>
					</if>
				</volist>
			</ul>
			<ul class="menu_list discount-list box "> 
				<li>
					<div>
						<h3>商品总价：
							<span class='fr totalPrice'>
								 {pigcms{$basic_price|floatval}
								<if condition="$config.open_extra_price eq 1 AND $extra_price gt 0">
									+{pigcms{$extra_price|floatval}{pigcms{$config.extra_price_alias_name}
								</if>
								<if condition="$total_score gt 0">
									+{pigcms{$total_score|floatval}{pigcms{$config['score_name']}
								</if>
							</span>
							<span class="fr">{pigcms{$config['Currency_symbol']}</span>
						</h3>
					</div>
				</li> 
				<volist name="pack_act_list" id="pack_vo">
				<li>
					<div>
						<h3>{pigcms{$pack_vo.pack_price}元任意{pigcms{$pack_vo.pack_num}件：
							<span class='fr'>
								-{pigcms{$config['Currency_symbol']}{pigcms{$pack_vo.pack_discount_price|floatval}
							</span>
						</h3>
					</div>
				</li>
				</volist>
				<gt name="store_discount_price" value='0'>
					<li>
						<div>
							<h3>店铺折扣：<span class='fr'>-{pigcms{$config['Currency_symbol']}{pigcms{$store_discount_price|floatval}
							<i style='font-size:12px;'>({pigcms{$store_discount|bcdiv=10|floatval}折)</i></span>
							</h3>
						</div>
					</li>
				</gt>
				<if condition="$discount_list">
	    			<volist name="discount_list" id="row">
	        			<li>
	        				<if condition="$row['discount_type'] eq 3">
	        					<div>
	        						<h3>
	        							<span class="discount_icon newuser"></span>
	        							商家首单优惠：满{pigcms{$config['Currency_symbol']}{pigcms{$row['money']|floatval}{pigcms{$config['Currency_txt']},减<strong style="display: inline;font-size:14px;">{pigcms{$config['Currency_symbol']}{pigcms{$row['minus']|floatval}</strong>{pigcms{$config['Currency_txt']}
	        							<span class="fr">-{pigcms{$config['Currency_symbol']}{pigcms{$row['minus']|floatval}</span>
	        						</h3>
	        					</div>
	        				<elseif condition="$row['discount_type'] eq 4" />
	        					<div>
	        						<h3>
	        							<span class="discount_icon minus"></span>
	        							商家满减优惠：满{pigcms{$config['Currency_symbol']}{pigcms{$row['money']|floatval}{pigcms{$config['Currency_txt']},减<strong style="display: inline;font-size:14px;">{pigcms{$config['Currency_symbol']}{pigcms{$row['minus']|floatval}</strong>{pigcms{$config['Currency_txt']}

	        							<span class="fr">-{pigcms{$config['Currency_symbol']}{pigcms{$row['minus']|floatval}</span>
	        						</h3>
	        					</div>
	        				<elseif condition="$row['discount_type'] eq 1" />
	        					<div>
	        						<h3>
	        							<span class="discount_icon system_newuser"></span>
	        							平台首单优惠：满{pigcms{$config['Currency_symbol']}{pigcms{$row['money']|floatval}{pigcms{$config['Currency_txt']},减<strong style="display: inline;font-size:14px;">{pigcms{$config['Currency_symbol']}{pigcms{$row['minus']|floatval}</strong>{pigcms{$config['Currency_txt']}
	        							<span class="fr">-{pigcms{$config['Currency_symbol']}{pigcms{$row['minus']|floatval}</span>
	        						</h3>
	        					</div>
	        				<elseif condition="$row['discount_type'] eq 2" />
	        					<div>
	        						<h3>
	        							<span class="discount_icon system_minus"></span>
	        							平台满减优惠：满{pigcms{$config['Currency_symbol']}{pigcms{$row['money']|floatval}{pigcms{$config['Currency_txt']},减<strong style="display: inline;font-size:14px;">{pigcms{$config['Currency_symbol']}{pigcms{$row['minus']|floatval}</strong>{pigcms{$config['Currency_txt']}
	        							<span class="fr">-{pigcms{$config['Currency_symbol']}{pigcms{$row['minus']|floatval}</span>
	        						</h3>
	        					</div>
	        				<elseif condition="$row['discount_type'] eq 6" />
	        					<gt name="vipLevelReduceMoney" value='0'>
		        					<div>
		        						<h3>
		        							<span class="discount_icon vip_discount" style='padding:0;'></span>
		        							{pigcms{$config.vip_card_alias|default='平台会员卡'}折扣：
		        							<span class="fr">-{pigcms{$config['Currency_symbol']}{pigcms{$row.vip_level_reduce_money|floatval}<i style='font-size:12px;'>({pigcms{$row.vip_level_discount}折)</i></span>
		        						</h3>
		        					</div>
	        					</gt>

                                <if condition="$key eq 'plat_discount'">
                                    <div>
                                        <h3>
                                            <span class="discount_icon system_minus"></span>
                                            平台满减优惠：满{pigcms{$config['Currency_symbol']}{pigcms{$row['money']|floatval}{pigcms{$config['Currency_txt']},<php>echo getFormatNumber($row['minus']*100);</php>折
                                            <span class="fr">-{pigcms{$config['Currency_symbol']}{pigcms{$row['minus2']|floatval}</span>
                                        </h3>
                                    </div>
                                </if>
	        				<else />

	        				</if>
	        			</li>
	    			</volist>
				</if>
				<!-- 打包费 -->
				<if condition="$packing_charge">
					<li>
						<div>
							<h3>{pigcms{$store['pack_alias']|default='打包费'}：
								<span class='fr'>{pigcms{$config['Currency_symbol']}{pigcms{$packing_charge|floatval}</span>
							</h3>
						</div>
					</li>
				</if>
				
				<li id="show_arrive_time" class="deliver_timeNews">
					<div  id="timeBtn">
						<h3>送达时间：
						    <i class="ico_arrow fr" style="margin-top: 6px;" ></i>
							<!-- data-basic_price="{pigcms{$dates.0.date_list.0.s_basic_price}" -->
							<span id="arriveTime" class="fr"> {pigcms{$dates.0.show_date} {pigcms{$dates.0.date_list.0.show_hour_minute}</span> 
						</h3>
					</div> 
				</li>
				
				<!-- 配送费 -->
				<li id="show_delivery_fee">
					<div>
						<h3 >
							{pigcms{$store['freight_alias']|default='配送费'}：
							<span class='fr'>{pigcms{$config['Currency_symbol']}{pigcms{$delivery_fee}</span>
		        			<if condition="$jd_on eq 1">
		        				<p style="margin-top: 5px;color: #ff510c;">京东运费：{pigcms{$config['Currency_symbol']}<span id="jd_freight">{pigcms{$jd_freight}</span></p>
		        			</if>
	        			</h3>
					</div>
				</li>
                <if condition="!empty($book_record)">
                    <li>
                        <div>
                            <h3 >
                                优惠价格：
                                <span class='fr'>{pigcms{$config['Currency_symbol']}{pigcms{$book_record.discount_price|floatval}</span>
                            </h3>
                        </div>
                    </li>
                </if>
				<li>
					<div>
						<h3>
							<span class="fr">小计：
                                <if condition="!empty($book_record)">
                                      <i class="font_size24">{pigcms{$config['Currency_symbol']}</i><span class="font_size24">{pigcms{$book_pay_price}</span>
                                    <else/>
                                    <i class="font_size24">{pigcms{$config['Currency_symbol']}</i><span class="font_size24 total_price">{pigcms{$price}</span>
                                </if>
								<if condition="$total_score gt 0">
									+ <span class="font_size24 total_score">{pigcms{$total_score|floatval}</span>{pigcms{$config['score_name']}
								</if>
							</span>
						</h3>
					</div>
				</li>
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
		<if condition="!empty($book_record)">
            <div class="pre_sale_info" style="margin-bottom: 20px;">
                <div class="keyvaluebox">
                    <strong class="kebox">
                        发货时间
                    </strong>
                    <div class="valuebox">
                        <if condition="$book_record['send_goods_type'] eq 1">
                            {pigcms{$book_record.send_goods_date}开始发货
                            <else/>
                            尾款支付{pigcms{$book_record.send_goods_day}天后发货
                        </if>

                    </div>
                </div>
                <div class="keyvaluebox" style="margin:15px 0">
                    <strong class="kebox">
                        阶段一：定金
                    </strong>
                    <div class="valuebox" style="color:#ff510c">
                        {pigcms{$config['Currency_symbol']} {pigcms{$book_record.bargin_price|floatval}
                    </div>
                </div>
                <div class="keyvaluebox">
                    <strong class="kebox">
                        阶段二：尾款
                    </strong>
                    <div class="valuebox">
                        {pigcms{$config['Currency_symbol']} {pigcms{$left_price|floatval}
                    </div>
                </div>
            </div>
        </if>

	</section>
	<div style="display:none;">
		<input class="hidden" id="order_id" name="order_id" value="{pigcms{$order_id}"/>
		<input class="hidden" id="ouserName" name="ouserName" value="{pigcms{$show_user_adress['name']}"/>
		<input class="hidden" id="ouserTel" name="ouserTel" value="{pigcms{$show_user_adress['phone']}"/>
		<input class="hidden" id="ouserAddres" name="ouserAddres" value="{pigcms{$show_user_adress['show_address']}"/>
		<input class="hidden" id="address_id" name="adress_id" value="{pigcms{$show_user_adress['adress_id']}"/>
		<input type="hidden" name="pick_address" value="{pigcms{$pick_address['area_info']['province']} {pigcms{$pick_address['area_info']['city']} {pigcms{$pick_address['area_info']['area']} {pigcms{$pick_address['name']} 电话：{pigcms{$pick_address['phone']}"/>
		<input type="hidden" name="pick_id" value="{pigcms{$pick_address['pick_addr_id']}"/>
		<input class="hidden" id="oarrivalDate" name="oarrivalDate" value="{pigcms{$dates.0.ymd}"/>
		<input class="hidden" id="oarrivalTime" name="oarrivalTime" value="{pigcms{$dates.0.date_list.0.hour_minute}"/>
		<input class="hidden" id="omark" name="omark" value="">
		<input class="hidden" id="invoice_head" name="invoice_head" value="">
		<input class="hidden" id="deliver_type" name="deliver_type" value="<if condition="$delivery_type eq 2 OR $pick_addr_id">1<else />0</if>"/>
		<input class="hidden" id="plat_deliver_fee" name="plat_deliver_fee" value="{pigcms{$dates.0.date_list.0.delivery_fee}">

		<if condition="$cue_field">
			<volist name="cue_field" id="vo">
				<if condition="$vo['type'] eq 1">
				<input class="hidden" id="cue_field_{pigcms{$key}_head" name="cue_field[{pigcms{$key}][txt]" value=""/>
				<input class="hidden" name="cue_field[{pigcms{$key}][title]" value="{pigcms{$vo.name}"/>
				</if>
			</volist>
		</if>
        <input class="hidden" id="jd_freight_h" name="jd_freight" value="{pigcms{$jd_freight}">
        <input class="hidden" id="area_code" name="area_code" value="{pigcms{$area_code}">
        <input class="hidden" id="area_address" name="area_address" value="{pigcms{$area_address}">
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
	<div class="addres_box" id="invoice_head_box">
		<ul>
			<li><textarea class="txt max" placeholder="请填发票抬头" id="invoice_head_txt"></textarea></li>
			<li class="btns_wrap">
			<span><a href="javascript:void(0);" class="comm_btn higher disabled" id="cancleInvoice">取消</a></span>
			<span><a href="javascript:void(0);" class="comm_btn higher" id="saveInvoice">确认</a></span>
			</li>
		</ul>
	</div>
	<if condition="$cue_field">
		<volist name="cue_field" id="vo">
			<div class="addres_box" id="cue_field_{pigcms{$key}_head_box">
				<ul>
					<li><textarea class="txt max" placeholder="请填{pigcms{$vo.name}" id="cue_field_{pigcms{$key}_head_txt"></textarea></li>
					<li class="btns_wrap">
						<span><a href="javascript:void(0);" class="comm_btn higher disabled" id="cancle_cue_field_{pigcms{$key}">取消</a></span>
						<span><a href="javascript:void(0);" class="comm_btn higher" id="save_cue_field_{pigcms{$key}">确认</a></span>
					</li>
				</ul>
			</div>
		</volist>
	</if>
</div>
<!-- 拼团、砍价活动不享受优惠提示信息 -->
<if condition="$group_id gt 0 or $bar_id gt 0">
	<p id="act_tips">
		<gt name='group_id' value='0'>拼团<else/>砍价</gt>商品不享受任何优惠
		<span class='close'></span>
	</p>
</if>
<if condition="!empty($book_record)">
    <div class="fixed_tips">
 
        <if condition="$book_record['rest_pay_type'] eq 1">
            尾款时间：{pigcms{$book_record.rest_start_time}~{pigcms{$book_record.rest_end_time}
            <else/>
             活动结束{pigcms{$book_record.rest_day}天后付尾款
        </if>


        <div class="pre_sale_info" style="margin-bottom: 20px;">
            <div class="keyvaluebox">
                <strong class="kebox">
                    发货时间
                </strong>
                <div class="valuebox">
                    <if condition="$book_record['send_goods_type'] eq 1">
                        {pigcms{$book_record.send_goods_date}开始发货
                        <else/>
                        尾款支付{pigcms{$book_record.send_goods_day}天后发货
                    </if>
				</div>
			</div>
		</div>
    </div>
</if>
<div class="fixed" style="height:50px;padding:14px;">
	<div style="position: absolute; bottom: 8px; left: 10px;right: 10px;">
		<span style="line-height: 31px;color: #ff510c;">
             <if condition="!empty($book_record)">
                 待支付：<strong>{pigcms{$config['Currency_symbol']}{pigcms{$book_record.bargin_price|floatval}</strong>
                 <else/>
                 待支付：
                 <strong>
                 	{pigcms{$config['Currency_symbol']}
                 	<span id="totalPrice_">
                 		<i class='total_price'>{pigcms{$price}</i>
                 		<if condition="$config.open_extra_price eq 1 AND $extra_price gt 0">
                 			+{pigcms{$extra_price|floatval}{pigcms{$config.extra_price_alias_name}
                 		</if>	
                 		<if condition="$total_score gt 0">
							+ {pigcms{$total_score|floatval}{pigcms{$config['score_name']}
						</if>
                 	</span>
                 </strong>
             </if>
			<gt name="discount_price" value='0'>
				<span id="cartNum_" class="color_3">&nbsp;|&nbsp;已优惠{pigcms{$config['Currency_symbol']}{pigcms{$discount_price}</span>
			</gt>
		</span>
		<span class="fr">
			<a href="javascript:;" class="comm_btn" id="submit_order" >确认订单</a>
		</span>

	</div>
</div>
<if condition="$cue_field">
	<volist name="cue_field" id="vo">
		<script type="text/javascript">
			// 添加自定义
			$('#cue_field_btn_{pigcms{$key}').bind('click', function(){
				var cue_field = $('#cue_field_{pigcms{$key}_txt').text();
				if(cue_field == '点击填写{pigcms{$vo.name}') cue_field = '';
				$('#cue_field_{pigcms{$key}_txt,#cue_field_{pigcms{$key}_head_txt').val(cue_field);
				$('#cue_field_{pigcms{$key}_head_box').dialog({title: '点击填写{pigcms{$vo.name}'});
			});

			$('#cancle_cue_field_{pigcms{$key}').bind('click', function(){
				$('#cue_field_{pigcms{$key}_head_box').dialog('close');
			});

			$('#save_cue_field_{pigcms{$key}').bind('click', function(){
				var cue_field = $('#cue_field_{pigcms{$key}_head_txt').val();
				if(cue_field == '') cue_field = '点击填写{pigcms{$vo.name}';
				$('#cue_field_{pigcms{$key}_txt').text(cue_field);
				$('#cue_field_{pigcms{$key}_head').val(cue_field);

				$('#cue_field_{pigcms{$key}_head_txt').val('');
				$('#cue_field_{pigcms{$key}_head_box').dialog('close');
			});
		</script>
	</volist>
</if>

<!--  -->
 <div class="deliver_time_box">
	<div class="mask"></div>
	<div class="timer">
		<div class="header">期望送达</div>
		<div class="content" style="margin-top: 0;">
			<div class="day">
				<volist name="dates" id="vo">
					<div class="item <if condition="$i eq 1">active</if>" data-date="{pigcms{$vo.ymd}">{pigcms{$vo.show_date}</div>
				</volist>
			</div>
			<div class="time" style="-webkit-overflow-scrolling:touch;">
				<volist name="dates" id="vo">
					<div class="item_box timer-{pigcms{$vo.ymd}" <if condition="$i neq 1">style="display:none;"</if>>
						<volist name="vo['date_list']" id="voo" key="k">

							<div class="item <if condition="$i eq 1 && $k eq 1">active</if>" data-basic_price="{pigcms{$voo.s_basic_price}"  data-show_date="{pigcms{$vo.show_date}" data-ymd="{pigcms{$vo.ymd}" data-time="{pigcms{$voo.hour_minute}" data-show_time="{pigcms{$voo.show_hour_minute}"  data-delivery_fee="{pigcms{$voo.delivery_fee_old}">{pigcms{$voo.show_hour_minute}<span class="price">({pigcms{$voo.delivery_fee_old}{pigcms{$config['Currency_txt']}配送费)</span></div>

						</volist>
					</div>
				</volist>
			</div>
		</div>
	</div>
</div>


<if condition="$show_buy_captcha">
	<div id="captchaSendCode"></div>
	<style>.dx_captcha_loading_overlay{z-index:1999;}.dx_captcha_basic-style-popup{z-index:2999;}.dx_captcha_clickword-style-popup{z-index:3999;}</style>
	<script src="https://cdn.dingxiang-inc.com/ctu-group/captcha-ui/index.js"></script>
	<script type="text/javascript">
		var show_buy_captcha = true;
		var CaptchaToken = "";
		var myCaptcha = _dx.Captcha(document.getElementById('captchaSendCode'), {
			appId: '{pigcms{$config.dingxiang_captcha_appid}',
			style: 'popup',
			success: function (token) {
				CaptchaToken = token;
				console.log('Captcha token:', token)
				myCaptcha.hide();
				$('#submit_order').addClass('disabled').html('提交中...');
				send_buy_form();
			},
			fail: function (res){
				console.log(res);
			}
		})
	</script>
<else/>
	<script type="text/javascript">var show_buy_captcha = false;var CaptchaToken = "";</script>
</if>


<script type="text/javascript">
//配送费（快递配送）
var delivery_fee={pigcms{$delivery_fee|default=0};
delivery_fee=parseFloat(delivery_fee);
//平台配送的配送费
var plat_delivery_fee="{pigcms{$dates.0.date_list.0.delivery_fee|default=0}";
plat_delivery_fee = parseFloat(plat_delivery_fee);
//店铺的起步配送费
var store_basic_price  ="{pigcms{$store.s_basic_price}";
store_basic_price = parseFloat(store_basic_price);
//默认平台配送费
var basic_price_default = "{pigcms{$dates.0.date_list.0.s_basic_price}";
basic_price_default = parseFloat(basic_price_default);

//支付总价
var pay_price={pigcms{$price|default=0|floatval};
pay_price=parseFloat(pay_price);
var plat_deliver_error = {pigcms{$plat_deliver_err};//平台配送错误提示
console.log(plat_deliver_error,'plat_deliver_error');
//$(' #show_arrive_time').css('display', 'none');
$(function(){
	$('.tipSS').hide();
	/*用户选择收货方式；1-快递；2-自提;3-平台配送*/
	$('.btn_express').click(function(){ 
		var not_shut = false  
		 $('#submit_order').removeClass('disabled');
		$('.tipSS1').hide();
		var key=$(this).data('key');
		$(this).removeClass('pick_in_store_click').addClass('pick_in_store').siblings('.btn_express').removeClass('pick_in_store').addClass('pick_in_store_click');
	   if(key==1){
			var total_price=(pay_price*100+delivery_fee*100)/100;
			if(delivery_fee>0){
				$('#show_delivery_fee').css('display','table');
			} 
			$('#show_delivery_fee .fr').html('{pigcms{$config['Currency_symbol']}'+delivery_fee);
			$('#li_pick').css('display', 'none');
			$('.tipSS,.deliver_way').hide()
			$('#li_delivery, #show_arrive_date,  #show_delivery_fee').css('display', 'table');
			$('#deliver_type').val(0);
			$('#deliver_timeNews').hide();
			$(' #show_arrive_date').css('display', 'none');
			$(' #show_arrive_time').css('display', 'none');
			$('.tipSS1').html()
			
		}else if(key==2){
			var total_price=pay_price; 
			$('#show_delivery_fee,.tipSS').css('display','none'); 
			$('#deliver_way').hide()
			$('#li_delivery').css('display', 'none');
			$('#li_pick').css('display', 'block');
			$(' #show_arrive_date,  #show_delivery_fee').css('display', 'none');
			$('#deliver_type').val(1);
			$('.deliver_timeNews').hide()
			$('.tipSS1').html()
			 
		}else if(key==3){ 
		   	var total_price=(pay_price*100+plat_delivery_fee*100)/100;//这里的配送费需要从新计算 
			$('#deliver_way').hide();
			if(plat_deliver_error.tips){ 
				$('#submit_order').addClass('disabled');
				$('.tipSS').show()
				$('.tipSS').html('<a  href="'+plat_deliver_error.url+'" style=" color:#ff510c">'+plat_deliver_error.tips+'</a>')
				not_shut = true
			}else{
				$('.tipSS').hide()
				$('#submit_order').removeClass('disabled');
			}
			$('#show_delivery_fee .fr').html('{pigcms{$config['Currency_symbol']}'+plat_delivery_fee);
			$('.deliver_timeNews').show()
			$('#show_arrive_time').css('display', 'table');
			$('#li_pick').css('display', 'none');
			$(' #show_delivery_fee').show()
			$(' #show_arrive_date').css('display', 'none');
			$('#deliver_type').val(3);
			$('#li_delivery').css('display', 'block');
			$('.tipSS1').show();  
			 
			var basic_price = $('#arriveTime').data('basic_price')
			if(!basic_price){
				basic_price = basic_price_default
			}
			if(basic_price != -1 ){	  
				if(pay_price < basic_price){ 
					var cha = basic_price - pay_price
					$('#li_delivery').hide()
					$('.tipSS1').show() 
					$('#submit_order').addClass('disabled');
					$('.tipSS1').html('<span>未满足起送价,还差<label>'+cha+'元</label>即可满足起送价哦！<a href="/wap.php?g=Wap&c=Mall&a=home" style="">【去凑单】</a> </span>')
				}else{
					$('.tipSS1').hide() 
					$('#li_delivery').show()
					if(!not_shut){
						$('#submit_order').removeClass('disabled');
					}
				} 
			}
			else{
				if(pay_price < store_basic_price){ 
					var cha = store_basic_price - pay_price
					$('.tipSS1').show();
                    if(!plat_deliver_error.tips){
					$('.tipSS1').html('<span>未满足起送价,还差<label>'+cha+'元</label>即可满足1起送价哦！<a href="/wap.php?g=Wap&c=Mall&a=home" style="">【去凑单】</a> </span>')
                    }
                    
                    $('#li_delivery').hide()
					$('#submit_order').addClass('disabled');
				}else{
					$('.tipSS1').hide() 
					$('#li_delivery').show()
					if(!not_shut){
						$('#submit_order').removeClass('disabled');
					}
				}
			}
		}
		var total_price = Math.round(total_price*100);
		$('.total_price').text(total_price/100);
	});
	$('.btn_express.pick_in_store').click();
	//关闭提示
	$(document).on('click','#act_tips .close',function(){
		$(this).parent().slideUp();
	});
});
<?php if($_GET['deliver_key']==3){ ?>
var total_price=(pay_price*100+plat_delivery_fee*100)/100;//这里的配送费需要从新计算		
$('#deliver_way').hide();
if(plat_deliver_error){
	$('.tipSS').show()
	$('.tipSS').html('<a href="'+plat_deliver_error.url+'">'+plat_deliver_error.tips+'</a>')
	$('#submit_order').addClass('disabled');
}else{
	$('.tipSS').hide()
	$('#submit_order').removeClass('disabled');
}

$('.deliver_timeNews').show()
$('#show_arrive_time').css('display', 'table');
$('#li_pick').css('display', 'none');
$(' #show_delivery_fee').show()
$(' #show_arrive_date').css('display', 'none');
$('#deliver_type').val(3);
$('#li_delivery').css('display', 'block');
<?php } ?>
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
<script type="text/javascript">
$(document).ready(function () {
    var jd_on = "{pigcms{$jd_on}";
	$('.cue_field').click(function(e){
        $(this).find('.mask').removeClass('hidden');
	});
	$('.address_tanceng li').click(function(e){
        var index = $(this).parents('.mask').data('index');
		var text = $(this).text();
		$('#cue_field_txt_' + index).text(text);
		if ($(this).data('s') == undefined) {
		  $('.cue_field_txt_' + index).val(text);
		} else {
		    $('.cue_field_txt_' + index).val('');
		}
		$('.mask').addClass('hidden');
        return false;
	});
	$('.mask').click(function(){
		$('.mask').addClass('hidden');
        return false;
	});

	$(window).scrollTop(1);
	$('div.fixed').css('bottom','0');
	$('.address_tanceng').css('bottom',$('div.fixed').height());

	// $('#post_package').click(function(){
		
	// 	$('#li_pick').css('display', 'none');
	// 	$('#li_delivery, #show_arrive_date, #show_arrive_time, #show_delivery_fee').css('display', 'block');
	// 	$('#deliver_type').val(0);
	// });
	// $('#post_package_express').click(function(){
	// 	// $(this).removeClass('pick_in_store_click').addClass('pick_in_store').siblings('.btn_express').removeClass('pick_in_store').addClass('pick_in_store_click');
	// 	$('#li_delivery').css('display', 'block');
	// 	$('#li_pick, #show_arrive_date, #show_arrive_time, #show_delivery_fee').css('display', 'none');
	// 	$('#deliver_type').val(0);
	// });
	// $('#pick_in_store').click(function(){
		 
	// 	// $(this).removeClass('pick_in_store_click').addClass('pick_in_store').siblings('.btn_express').removeClass('pick_in_store').addClass('pick_in_store_click');
	// 	$('#li_pick').css('display', 'block');
	// 	$('#li_delivery, #show_arrive_date, #show_arrive_time, #show_delivery_fee').css('display', 'none');
	// 	$('#deliver_type').val(1);
	// });

	// 添加备注
	$('#remarkBtn').bind('click', function(){
		var remark = $('#remarkTxt').text();
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

	// 添加发票
	$('#invoiceBtn').bind('click', function(){
		var invoice = $('#invoiceTxt').text();
		if(invoice == '点击添加发票抬头') invoice = '';
		$('#invoice_head_txt').val(invoice);
		$('#invoice_head_box').dialog({title: '添加发票抬头'});
	});

	$('#cancleInvoice').bind('click', function(){
		$('#invoice_head_box').dialog('close');
	});

	$('#saveInvoice').bind('click', function(){
		$('#invoiceTxt').text($('#invoice_head_txt').val());
		$('#invoice_head_txt').val('');
		$('#invoice_head_box').dialog('close');
	});
    //京东发票
    // $("input[name='invoiceType']").change(function() {
    //     if (this.value == '2') {
    //         $('.companyName').show();
    //         $('.regCode').show();
    //         $("input[name='selectedInvoiceTitle']").attr("checked",false);
    //         $("input[name='selectedInvoiceTitle'][value='5']").attr("checked",true);
    //         $("input[name='selectedInvoiceTitle']").attr("disabled",true);
    //         $("input[name='invoiceContent']").attr("checked",false);
    //         $("input[name='invoiceContent'][value='1']").attr("checked",true);
    //         $("input[name='invoiceContent']").attr("disabled",true);
    //     }
    //     if (this.value == '3') {
    //         if($("input[name='selectedInvoiceTitle']:checked").val()=='4'){
    //             $('.companyName').hide();
    //             $('.regCode').hide();
    //         }
    //         $("input[name='selectedInvoiceTitle']").attr("disabled",false);
    //         $("input[name='invoiceContent']").attr("disabled",false);
    //     }
    // });
    //
    // $("input[name='selectedInvoiceTitle']").change(function() {
    //     if (this.value == '5') {
    //         $('.companyName').show();
    //         $('.regCode').show();
    //     }
    //     if (this.value == '4') {
    //         $('.companyName').hide();
    //         $('.regCode').hide();
    //     }
    // });
	
	//if(!(/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase()) || (/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) && /(life_app)/.test(navigator.userAgent.toLowerCase())))){
		$(".a-link").click(function(){
			var address_plat_deliver = 0;
			var deliver_key = $('.pick_in_store').data('key');
			var url = $(this).attr('data-href')+'&deliver_key='+deliver_key;
			location.href = url;
		});
	//}

	$("#submit_order").click(function(){
		
		// layer.open({
		// 	content:'预售商品，定金不退，是否继续下单？',
		// 	btn: ['确定','取消'],
		// 	yes: function (index) {
		//     },
		
		// });

		var score_less = "{pigcms{$score_less}";

		if(score_less=='1'){
			score_location_url = "{pigcms{:U('My_card/plat_prepay')}";
			layer.open({
				content: '{pigcms{$config.score_name}不足，立即前往获取{pigcms{$config.score_name}',
				btn: ['确定','取消'],
				yes: function (index) {
		 			location.href = score_location_url;
		        },

		    });
		    return false;
		}
		
        if($('#error_tips').text() != ''){
            motify.log($('#error_tips').text());
            return false;
        }

        // if($("input[name='selectedInvoiceTitle']:checked").val()=='5'){
        //     if($('#companyName').val()==''){
        //         motify.log('请填写发票抬头！');
        //         return false;
        //     }
        //     if($('#regCode').val()==''){
        //         motify.log('请填写纳税人识别号！');
        //         return false;
        //     }
        // }
        if(typeof(jd_on) !="undefined" && jd_on >0) {
            if ($('#carea').text() != '没有下级地址') {
                var val = $('input:radio[name="town"]:checked').val();
                if (val == null) {
                    motify.log("街道/乡镇必须选择!");
                    return false;
                }
            }
        }
        // if($('#email').val()==''){
        //     motify.log('邮箱必须填写！');
        //     return false;
        // }

        // $("input[name='selectedInvoiceTitle']").attr("disabled",false);
        // $("input[name='invoiceContent']").attr("disabled",false);

		if($('#deliver_type').val() == 0 && $('#address_id').val() == ''){
			motify.log('请您先添加配送地址');
			return false;
		}
		if(!$(this).hasClass('disabled')){
			<?php
				if($cue_field){
					foreach($cue_field as $key=>$value){
						if($value['iswrite']){
			?>
							if($('#cue_field_<?php echo $key; ?>_head').val() == ''){
								motify.log("请填写<?php echo $value['name'];?>");
								return false;
							}
			<?php
						}
					}
				}
			?>
			$(this).addClass('disabled').html('提交中...');

// 			var wo_delivery_time = $.trim($("#arriveTime").html());
// 			if(wo_delivery_time == '尽快送出'){
// 				wo_delivery_time = '';
// 			}
// 			$('#oarrivalTime').val(wo_delivery_time);

			var wo_memo = $.trim($("#remarkTxt").html());

			if(wo_memo == '点击添加订单备注') {
				wo_memo = '';
			}
			$('#omark').val(wo_memo);
			var invoice_head = $.trim($("#invoiceTxt").html());
			if(invoice_head == '点击添加发票抬头') {
				invoice_head = '';
			}
			$('#invoice_head').val(invoice_head);
			
			if(show_buy_captcha){
				$(this).removeClass('disabled').html('确认订单');
				if(!CaptchaToken || CaptchaToken == ''){
					myCaptcha.show();
				}else{
					myCaptcha.reload();
					myCaptcha.show();
				}
			}else{
				send_buy_form();
			}
			
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

function send_buy_form(){
	$.post($('#cart_confirm_form').attr('action'), $('#cart_confirm_form').serialize() + '&CaptchaToken=' + CaptchaToken, function(response){
		if (response.status) {
			if(typeof wx !=='undefined' && window.__wxjs_environment === 'miniprogram'){
				wx.miniProgram.navigateTo({
					url:'/pages/shop_new/confirmOrder/pay?order_id='+ response.info.order_id +'&type=mall'
					,complete:function(){
						$("#submit_order").removeClass('disabled').html('确认订单');
					}
				});
			}else if(motify.checkLifeApp() && motify.checkIos() && motify.getLifeAppVersion() >= 1420){
				window.webkit.messageHandlers.appJSBridgeMethod.postMessage({name: 'gopay', orderID: String(response.info.order_id), type: 'mall', backNoFresh:'1'});
				$("#submit_order").removeClass('disabled').html('确认订单');
			}else if(motify.checkLifeApp() && motify.checkAndroid() && motify.getLifeAppVersion() >= 1000){
				window.lifepasslogin.payCheck("mall", response.info.order_id);
				$("#submit_order").removeClass('disabled').html('确认订单');
			}else{
				$("#submit_order").removeClass('disabled').html('确认订单');
				//console.log(response.info.order_id);
				location.href = "{pigcms{:U('Pay/check',array('type'=>'mall'))}&order_id="+response.info.order_id;
			}
		} else if(response.info == 'need_check_auth'){
			location.href = response.url;
		} else {
			//alert(response.info);
			$("#submit_order").removeClass('disabled').html('确认订单');
		}
	});
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


<script>
	    $('#show_arrive_time').click(function(){ 
			$('.deliver_time_box .mask').removeClass('hidden');
			$('.deliver_time_box').show();
		});
		$('.deliver_time_box .mask').click(function(){
			$('.deliver_time_box').hide();
		});
		
		$('.deliver_time_box .day .item').click(function(){
			$(this).addClass('active').siblings().removeClass('active');
			$('.timer-'+$(this).data('date')).show().siblings().hide();
			$('.deliver_time_box .time').scrollTop(0);
		});
		$('.deliver_time_box .time .item').click(function(){ 
			 
		    $('.deliver_time_box .time .item').removeClass('active');
			$(this).addClass('active');
			$('#arriveTime').html($(this).data('show_date')+' '+$(this).data('show_time')); 
			console.log($(this).data('basic_price'))
			var basic_price= $(this).data('basic_price')
			$('#arriveTime').attr('data-basic_price',basic_price) 
			
			$('#oarrivalDate').val($(this).data('ymd'));
			$('#oarrivalTime').val($(this).data('time'));
			$('#delivery_fee').val($(this).data('delivery_fee')); 
			console.log($(this).data('basic_price'))
			if($(this).data('basic_price') != -1 ){  
				if(pay_price < $(this).data('basic_price')){
					$('.tipSS1').show() 
					var cha = $(this).data('basic_price') - pay_price
					$('.tipSS1').html('<span>未满足起送价,还差<label>'+cha+'元</label>即可满足起送价哦！<a href="/wap.php?g=Wap&c=Mall&a=home" style="">【去凑单】</a> </span>')
				    $('#li_delivery').hide()
					$('#submit_order').addClass('disabled');
				}else{
					$('.tipSS1').hide() 
					$('#li_delivery').show() 
					$('#submit_order').removeClass('disabled');
				} 
			}else{
				if(pay_price < store_basic_price){
					$('.tipSS1').show() 
					var cha = store_basic_price - pay_price
					$('.tipSS1').html('<span>未满足起送价,还差<label>'+cha+'元</label>即可满足起送价哦！<a href="/wap.php?g=Wap&c=Mall&a=home" style="">【去凑单】</a> </span>')
				    $('#li_delivery').hide()
					$('#submit_order').addClass('disabled');
				}else{
					$('.tipSS1').hide() 
					$('#li_delivery').show()
					$('#submit_order').removeClass('disabled');
				}
			}
			
			
			$('#show_delivery_fee strong').html('{pigcms{$config['Currency_symbol']}'+$(this).data('delivery_fee'));
			$('#show_delivery_fee span').html('{pigcms{$config['Currency_symbol']}'+$(this).data('delivery_fee'));
			
			$('#count_price').data('deliver', $(this).data('delivery_fee'));
	        var delivery_fee = $(this).data('delivery_fee');
	        $('#plat_deliver_fee').val(delivery_fee)
			
			$('.total_price').html(delivery_fee+Number($('.totalPrice').text()))
	        //TODO
			setTimeout(function(){
				$('.deliver_time_box').hide();
			},150);
		});
		
		$('.curWay a:nth-child(2)').addClass('pick_in_store')
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