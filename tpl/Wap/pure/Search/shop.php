<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$config.shop_alias_name}搜索</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?210">
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/list.css?210"/>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
		<script type="text/javascript">
			var location_url="{pigcms{$config.site_url}/appapi.php?c=Shop&a=search&key="+"{pigcms{:urlencode($keywords)}",now_sort="<if condition="!empty($now_sort)">{pigcms{$now_sort}<else/>defaults</if>";
			//location_url+='&lat={pigcms{$user_long.lat}&long={pigcms{$user_long.long}'
			var user_long, deliverName = "{pigcms{$config['deliver_name']}";
			var voice_type = "shop";
			var mobile_color = '{pigcms{$config["mobile_head_color"]}'; 
		</script>
		<script type="text/javascript" src="{pigcms{$static_path}js/shopsearch.js?210" charset="utf-8"></script>
		<style>
			.goodList li{
					padding: 8px 0 6px 0px;
			}
			.goodList{
				border-top:none;
			}
			.goodList .goods_name{
				margin-left:70px;
				font-size:14px;
			}
			.goodList .goods_sale{
				margin-left:70px;
				font-size:12px;
					color: #999;
			}
			.goodList .goods_price{
				float:right;
				margin-top:-40px;
				color:#06c1ae;
			}
			.showMoreGoods{
				    border-bottom: none;
				height: 26px;
				line-height: 26px;
				text-align: center;
				color: #999;
			}
			.showMoreGoods:after{
				content: "";
				display: inline-block;
				margin-left: 6px;
				width: 8px;
				height: 8px;
				border: 1px solid #999;
				border-width: 0 1px 1px 0;
				border-top-width: 0px;
				border-right-width: 1px;
				border-bottom-width: 1px;
				border-left-width: 0px;
				-webkit-transform: rotate(45deg);
				margin-top: 6px;
				vertical-align: top;
			}
			.hideMoreGoods:after{
				content: "";
				display: inline-block;
				margin-left: 6px;
				width: 8px;
				height: 8px;
				border: 1px solid #999;
				border-width: 0 1px 1px 0;
				border-top-width: 0px;
				border-right-width: 1px;
				border-bottom-width: 1px;
				border-left-width: 0px;
				-webkit-transform: rotate(225deg);
				margin-top: 6px;
				vertical-align: top;
			}
		
		</style>
	</head>
	<body>
		<section class="searchBar pageSliderHide <if condition="!$is_weixin_browser">wap</if>" style="background-color:white;border-bottom:1px solid #edebeb;">
			<div class="searchBox">
				<form id="search-form" action="{pigcms{:U('Search/shop')}" method="post">
					<input type="search" id="keyword" name="w" placeholder="请输入搜索词" autocomplete="off" value="{pigcms{$keywords}"/>
				</form>
			</div>
			<div class="voiceBtn"></div>
		</section>
		<section class="searchBox pageSliderHide">
			<ul>
				<li class="dropdown-toggle active"  data-url-type="openLeftWindow" style='color:{pigcms{$config["mobile_head_color"]};border-color:{pigcms{$config["mobile_head_color"]}'><span class="nav-head-name">{pigcms{$config.shop_alias_name}</span></li>
				<li class="dropdown-toggle link-url" data-url="{pigcms{:U('Search/group',array('w'=>urlencode($keywords)))}" target="self"><span class="nav-head-name">{pigcms{$config.group_alias_name}</span></li>
				<li class="dropdown-toggle link-url" data-url="{pigcms{:U('Search/meal',array('w'=>urlencode($keywords)))}" target="self"><span class="nav-head-name">{pigcms{$config.meal_alias_name}</span></li>
				<if condition="$config.appoint_alias_name neq ''"><li class="dropdown-toggle link-url" data-url="{pigcms{:U('Search/appoint',array('w'=>urlencode($keywords)))}" target="self"><span class="nav-head-name">{pigcms{$config.appoint_alias_name}</span></li></if>
                <li class="dropdown-toggle link-url" data-url="{pigcms{:U('Search/worker',array('w'=>urlencode($keywords)))}" style="display:none" target="self"><span class="nav-head-name"><?php echo $config['appoint_worker_name'] ? $config['appoint_worker_name'] : '技师'; ?></span></li>
			</ul>
		</section>
		<php>$no_footer = true;</php>
		<include file="Public:footer"/>
		<div id="container">
			<div id="scroller">
				<div id="pullDown">
					<span class="pullDownIcon"></span><span class="pullDownLabel">下拉可以刷新</span>
				</div>
				<script id="groupListBoxTpl" type="text/html">
					{{# for(var i = 0, len = d.length; i <len; i++){ }}
					{{# console.log(i) }}
						<dd class="page-link link-shop" data-store_id="{{ d[i].store_id }}" data-url="/wap.php?c=Shop&a=index#shop-{{ d[i].store_id }}" data-url-type="openRightFloatWindow" {{# if(d[i].is_close==1){ }}style="opacity:0.6;"{{# } }}>
							<div class="dealcard-img imgbox">
								{{# if(d[i].isverify == 1){ }}
									<img src="./static/images/kd_rec.png" style="width: 41px;height: 15px;position: absolute;z-index: 15;margin: 2px 0 0 0;">
								{{# } }}
								<img src="{{ d[i].image }}" alt="{{ d[i].name }}" style="height:60px;margin-left:0px;"/>
								{{# if(d[i].is_close==1){ }}<div class="closeTip">休息中</div>{{# } }}
							</div>
							<div class="dealcard-block-right">
								<div class="brand">{{ d[i].name }}<em class="location-right">{{# if(user_long != '0'){ }}{{ d[i].range }}{{# } }}</em></div>
								<div class="title ">
									<span class="star"><i class="full"></i><i class="full"></i><i class="full"></i><i class="half"></i><i></i></span>
                                    {{# if (d[i].month_sale_count > 0) { }}
                                    <span class="sold">已售{{ d[i].month_sale_count }}单</span>
                                    {{# } else if (d[i].is_new) { }}
                                    <span>新店上市</span>
                                    {{# } else { }}
                                    <span>　</span>
                                    {{# } }}
                                    <span class="location-right">
                                    	{{# if(d[i].delivery){ }}
											<em style="color:{{mobile_color}}">{{ d[i].delivery_time }}分钟</em>
										{{# } if (d[i].deliver_type == 2 || d[i].deliver_type == 3 || d[i].deliver_type == 4) { }}
											<em class="pick" style="color:{{mobile_color}};border-color:{{mobile_color}};">门店自提</em>
										{{# } }}
                                    </span>
								</div>
								{{# if(d[i].deliver_type != 2){ }}
									<div class="price">
										<span>起送价 {pigcms{$config['Currency_symbol']}{{ d[i].delivery_price }}</span>
										{{# if({pigcms{$config['shop_show_delivermoney']}){ }}
										<span class="delivery">配送费 {pigcms{$config['Currency_symbol']}{{ d[i].delivery_money }}</span>
										{{# } }}
										{{# if(d[i].deliver_type == 0 || d[i].deliver_type == 3){ }}
											<em class="location-right" style="background-color:{{mobile_color}};">{{ deliverName }}</em>
										{{# }else if (d[i].deliver_type == 1 || d[i].deliver_type == 4){ }}
											<em class="location-right" style="background-color:{{mobile_color}};">商家配送</em>
										{{# }else { }}
											<em class="location-right" style="background-color:{{mobile_color}};">快递配送</em>
										{{# } }}
									</div>
								{{# } }}
							</div>
							{{# if(d[i].coupon_count > 0){ }}
								<div class="coupon {{# if(d[i].coupon_count > 2){ }}hasMore{{# } }}">
									<ul>
										{{# for(var e = 0, lenc = d[i].coupon_list.length ; e < lenc; e++){ }}
												
											{{# var tmpCouponList =  d[i].coupon_list[e]; }}
											{{# if(tmpCouponList['type']=='invoice'){ }}
												<li><em class="merchant_invoice"></em>{{ tmpCouponList['value'] }}</li>
											{{# } }}
											
											{{# if(tmpCouponList['type']=='discount'){ }}
												<li><em class="merchant_discount"></em>{{ tmpCouponList['value'] }}</li>
											{{# } }}
											{{# if(tmpCouponList['type']=='minus'){ }}
												<li><em class="merchant_minus"></em>{{ tmpCouponList['value'] }}</li>
											{{# } }}
											{{# if(tmpCouponList['type']=='newuser'){ }}
												<li><em class="newuser"></em>{{ tmpCouponList['value'] }}</li>
											{{# } }}
											{{# if(tmpCouponList['type']=='delivery'){ }}
												<li><em class="delivery"></em>{{ tmpCouponList['value'] }}</li>
											{{# } }}
											{{# if(tmpCouponList['type']=='system_minus'){ }}
											<li><em class="system_minus"></em>{{ tmpCouponList['value'] }}</li>
											{{# } }}
											{{# if(tmpCouponList['type']=='system_newuser'){ }}
												<li><em class="system_newuser"></em>{{ tmpCouponList['value'] }}</li>
											{{# } }}
										{{# } }}
									</ul>
									{{# if(d[i].coupon_count > 2){ }}
										<div class="more">{{ d[i].coupon_count }}个活动</div>
									{{# } }}
								</div>
							{{# } }}	
						</dd>
					{{# } }}
				</script>
				<section class="listBox">
					<dl class="dealcard"></dl>
					<div class="no-deals <if condition="!empty($shop_list)">hide</if>"><div><img src="{pigcms{$static_path}img/search.png"></div>没有搜索到相关数据</div>
					<div class='paotui <if condition="!empty($group_list)">hide</if>' style='display: none;'>
					    <img class="icon" src="{pigcms{$static_path}images/paotui.png">
					    <div class='desc '>
					      <div>找不到店铺与商品？</div>
					      <div class='second'>骑手们帮您全城跑腿</div>
					    </div>
					    <a href="javascript:;" class='btn'>一键下单</a>
					</div>
				</section>
				<div id="pullUp" <if condition="$totalPage lt 2">class="noMore loading" style="display:none;"</if>>
					<span class="pullUpIcon"></span><span class="pullUpLabel">上拉加载更多</span>
				</div>
			</div>
		</div>
		{pigcms{$hideScript}
		<script type="text/javascript">
			console.log('4543444444444444', '{pigcms{$config.pay_wxapp_paotui_appid}')
			if(typeof wx != "undefined"){
				wx.ready(function(){
					if(window.__wxjs_environment === 'miniprogram'){
						$(document).on('click','.link-shop',function(event){
							wx.miniProgram.navigateTo({url: "/pages/shop_new/shopDetail/shopDetail?store_id="+$(this).data('store_id')});
							event.preventDefault();
							event.stopPropagation();
							return false;
						});

						if ('{pigcms{$config.pay_wxapp_paotui_appid}') {
							$('.paotui').show()
							$('.paotui .btn').click(function(){
								wx.miniProgram.navigateTo({url: '/pages/index/location_wxapp?appid={pigcms{$config.pay_wxapp_paotui_appid}&pageTitle='+encodeURIComponent('帮我买')});
								return false;
							});
						}
						
					}
				});
			}
		</script>
	</body>
</html>