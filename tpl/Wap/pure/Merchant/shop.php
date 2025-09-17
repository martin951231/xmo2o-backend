<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>店铺详情</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?210"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/shop.css?210"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/css_whir.css?a=1123688"/>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/shop.js?211" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/swiper.min.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
		<!--<script src="scripts/html5shiv.min.js"></script>-->
		<style type="text/css">
			.details{ position: relative; }
			.collection{ position: absolute; width: 35px; height: 35px; border-radius: 100%; background: url({pigcms{$static_path}images/3.png) center no-repeat; z-index: 999; top:10px; right: 10px; }
			.collection:after{ display: block;  content: ''; width: 16px; height: 16px; background: url({pigcms{$static_path}images/2.png) center no-repeat; background-size: 16px; position: absolute; ); top: 50%; left: 50%;  margin: -8px; }
			.collection.on:after{ background: url({pigcms{$static_path}images/1.png) center no-repeat; background-size: 16px; }
			.albumContainer {position: fixed;width: 100%;height: 100%;left: 0;top: 0;background: #000;z-index: 1000;display: none;overflow: hidden;-webkit-transform-origin: 0px 0px;opacity: 1;-webkit-transform: scale(1,1);}
			.albumContainer .swiper-close {right: 0px;top: 0px;text-align:right;padding-right: 10px;line-height: 50px;position: absolute;z-index: 21;width: 50px; height: 50px;color: white;font-size: 20px; font-family: "Arial";}
			.albumContainer .swiper-pagination {position: absolute;z-index: 20;left: 0;top: 20px;text-align: center;width: 100%;height: 20px;}
			.albumContainer .swiper-slide img{ width: 100%; max-height: 85% }
			.albumContainer .swiper-wrapper{ padding-top: 50px; }
			.albumContainer .swiper-pagination-switch{ background: #fff; opacity: 1; }
			.albumContainer .swiper-active-switch {background: #06c1ae;}
			.albumContainer .swiper-wrapper{
				position: relative;
				width: 100%;
				height: 100%;
				z-index: 1;
				display: -webkit-box;
				display: -moz-box;
				display: -ms-flexbox;
				display: -webkit-flex;
				display: flex;
				-webkit-transition-property: -webkit-transform;
				-moz-transition-property: -moz-transform;
				-o-transition-property: -o-transform;
				-ms-transition-property: -ms-transform;
				transition-property: transform;
				-webkit-box-sizing: content-box;
				-moz-box-sizing: content-box;
				box-sizing: content-box;
			}
			.swiper-container,.swiper-wrapper,.swiper-slide{
				width: 100%;
				height: 100%;
			}
			.swiper-container {
				margin: 0 auto;
				position: relative;
				overflow: hidden;
				-webkit-backface-visibility: hidden;
				-moz-backface-visibility: hidden;
				-ms-backface-visibility: hidden;
				-o-backface-visibility: hidden;
				backface-visibility: hidden;
				z-index: 1;
			}
			.swiper-slide{
				float: left;
			}
			.swiper-pagination {
				position: absolute;
				z-index: 20;
				left: 0px;
				width: 100%;
				text-align: center;
				bottom:4px;
			}
			.swiper-pagination-switch {
				display: inline-block;
				width: 6px;
				height: 6px;
				border-radius: 8px;
				background: black;
				margin-right:5px;
				opacity: 0.14;
				cursor: pointer;
			}
			.swiper-active-switch {
				background: #06c1ae;
				opacity: 1;
			}

			.banner{
				height: 119px;;
			}
			.banner img{
				height:100%;
				width:100%;
			}
			.slider{
				background-color: white;
				border-bottom: 1px solid #edebeb;
				margin-bottom: 10px;
			}

			.icon-list .icon {
				float: left;
				width: 25%;
				text-align: center;
			}
			.icon-list.num10 .icon {
				float: left;
				width: 20%;
				text-align: center;
			}
			.icon-list .icon > a {
				padding-top: 12px;
				display: block;
			}
			.swiper-container2  .icon-list .icon > a {
				height:auto
			}
			.icon-list .icon-circle {
				display: block;
				margin: auto;
				width: 40px;
				height: 40px;
				text-align: center;
				color: white;
				margin-bottom: 3px;
			}
			.icon-list.num10 .icon-circle {
				width:40px;
				height:40px;
			}
			.icon-list .icon-circle img {
				width: 100%;
				height: 100%;
			}
			.icon-list .icon-desc {
				text-align: center;
				color: #999;
			}
			.icon-list.num10 .icon-desc {
				font-size:12px;
			}
			.slider .swiper-pagination{
				bottom:2px;
			}
			.swiper-pagination-bullet{
				background-color: white;
				border:1px solid #d8d8d8;
				width:5px;
				height:5px;
				margin-right: 5px;
				opacity: 1;
			}
			.swiper-pagination-bullet-active{
				background: #06c1ae;
				border:1px solid #06c1ae;
			}
			.tag{
				display: inline-block;
				margin-left: 3px;
				border: 1px solid #f58300;
				color: #f58300;
				padding: 1px 3px;
				border-radius: 2px;
				font-size: 12px;
				line-height: 12px;
			}
			.view_album img {
				width: 68px;
				height: 68px;
			}
			.img_box {
				width: 45px;
				height: 45px;
				border-radius: 28px;
				float: left;
				overflow: auto;
				margin: 10px 10px 1px 0;
			}
			.img_box img {
				width: 100%;
				height: 100%;
				display: block;
				object-fit: cover;
			}
			.w100{width:100%}
			.h100{height:100%}
			.pingStar{
				background: url({pigcms{$static_path}images/tt_09.png);
				width: 90px;
				height: 13px;
				overflow: hidden;
				position: relative;
				background-size: 18px;
				margin-left: 24px;
			}
			.pingStar p {
				background: url({pigcms{$static_path}images/tt_07.png);
				left: 0;
				height: 13px;
				background-size: 18px;
			}
			.daohang{
				color: #fff;
				background: #f03c3c;
				padding: 1px 5px;
				margin-left: 5px;
				border-radius: 3px;
			}
			.nav_first{
				position: absolute;
				width: 96px;
				height: 35px;
				/* border-radius: 100%; */
				background-color: rgba(255,255,255,0.2);
				z-index: 999;
				top: 57px;
				right: 10px;
				line-height: 35px;
				text-align: center;
				border-radius: 2px;
			}
		.nav_second{
			position: absolute;
			width: 96px;
			height: 35px;
			/*border-radius: 100%;*/
			background-color: rgba(255,255,255,0.2);
			z-index: 999;
			top: 98px;
			right: 10px;
			line-height: 35px;
			text-align: center;
			border-radius: 2px;
		}
		</style>
		<script type="text/javascript">
			$(function(){
				$(".collection").click(function(){
					var store_id = "2";
					var collectionUrl = "/wap.php?g=Wap&c=Foodshop&a=store_collection";
					var uid = "{pigcms{$uid}";
					if(!uid){
						layer.open({content:'请先登录再进行收藏！',btn: ['确定'],end:function(){location.href="/wap.php?g=Wap&c=Login&a=index";}});
						return false;
					}
					if($(this).hasClass("on")){
						$(this).removeClass("on");
						$.post(collectionUrl,{'store_id':store_id},function(data){
							layer.open({
								content: data.msg
								,skin: 'msg'
								,anim: 'up'
								,time: 2
							});
						},'json');
					}else{
						$(this).addClass("on");
						$.post(collectionUrl,{'store_id':store_id},function(data){
							layer.open({
								content: data.msg
								,skin: 'msg'
								,anim: 'up'
								,time: 2
							});
						},'json');
					}
				})
			})
		</script>
	</head>
	<body style="zoom: 1;">
	<div class="collection <if condition="$collection_info">on</if>"></div>
	<if condition="$now_store['supplier_nav_first_link']">
	<div class="nav_first" ><a href="{pigcms{$now_store['supplier_nav_first_link']}">{pigcms{$now_store['supplier_nav_first_name']}</a></div>
	</if>
	<if condition="$now_store['supplier_nav_second_link']">
	<div class="nav_second" ><a href="{pigcms{$now_store['supplier_nav_second_link']}">{pigcms{$now_store['supplier_nav_second_name']}</a></div>
	</if>
	<section class="headshop">
		<div class="swiper-container swiper-container1 swiper-container-horizontal" >
			<div class="swiper-wrapper h100" style="transition-duration: 300ms; ">
				<volist name="now_store.all_pic" id="vo">
				<div class="swiper-slide h100" style="width: 375px;"><a href="javascrip:;"><img src="{pigcms{$vo}" class="w100 h100"></a></div>
				</volist>
			</div>
			<div class="swiper-pagination swiper-pagination1"><span class="swiper-pagination-bullet"></span><span class="swiper-pagination-bullet"></span><span class="swiper-pagination-bullet swiper-pagination-bullet-active"></span></div>
			<div class="shop_bot" style="z-index: 30;">
				<span> {pigcms{$now_store.name}</span>
				<span style="float:right;margin-right: 16px;"> <?php echo count($now_store['all_pic']);?>张</span>
			</div>
		</div>
		<script>
			var mySwiper1 = new Swiper('.swiper-container1', {
				autoplay: 3000,
				pagination : '.swiper-pagination1'
			})
		</script>
	</section>
	<if condition="$wap_index_slider">
		<section class="slider" >
			<div class="<if condition="count($wap_index_slider) gt 1">swiper-container</if> swiper-container2" <if condition="$slider_num elt ($wap_index_slider_number/2)">style="height:84px;"<else />style="height:168px;"</if>>
			<div class="swiper-wrapper">
				<volist name="wap_index_slider" id="vo">
					<div class="swiper-slide">
						<ul class="icon-list num{pigcms{$wap_index_slider_number}">
							<volist name="vo" id="voo">

								<li class="icon">
									<a href="{pigcms{$voo.url}" onclick="turn('{pigcms{$voo.url}');">
														<span class="icon-circle">
															<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$voo.pic}">
														</span>
										<span class="icon-desc">{pigcms{$voo.name}</span>
									</a>
								</li>
							</volist>
						</ul>
					</div>
				</volist>
			</div>
			<if condition="count($wap_index_slider) gt 1">
				<div class="swiper-pagination swiper-pagination2"></div>
			</if>
			</div>
	</section>
	</if>
		<section class="BusinessHours clr">
			<div class="">
				<img src="./static/images/dianpu.png" style="width:18px; height:20px;margin-top:8px; margin-right:10px;float:left">
				<span style="padding-right: 10px;">{pigcms{$now_store.name}</span>
			</div>
			<div class="pingStar">
				<if condition="$store_score">
				<p style="width: <?php echo 90*$store_score['score_all']/$store_score['reply_count']/5;?>px;"></p>
					<else/>
					<p style="width: 70%;"></p>
				</if>
			</div>
			<if condition='$now_store["isverify"]'>
			<img src="./static/images/rec_2.png" style="width:18px; height:20px;margin-top:8px; margin-right:10px;float:left">
			<span style="float:left">认证商家</span><br>
			</if>
			<span class="on on1 fl">营业时间：{pigcms{$now_store.business_time}</span>	<br>
			<div class="">
				<img src="./static/images/shopAdress.png" style="width:18px; height:20px;margin-top:8px; margin-right:10px;float:left">
				<a href="{pigcms{:U('Group/addressinfo',array('store_id'=>$now_store['store_id']))}">
					<span style="padding-right: 10px;">{pigcms{$now_store.area_name}{pigcms{$now_store.adress}
						<span class="daohang">导航</span>
					</span>
				</a>
			</div>
			<div class="">
				<img src="./static/images/shopPhone.png" style="width:18px; height:20px;margin-top:8px; margin-right:10px;float:left">
				<a href="tel:{pigcms{$now_store.phone}">{pigcms{$now_store.phone}</a>	</div>
		</section>
		<if condition='$now_store["wifi_account"]'>
		<section class="wifi clr">
			<div class="wifi_left fl">
				<span class="wifi_top"></span>
				<span class="wifi_font" style="display: none;">点击按钮连接无线网</span>
				<span class="wifi_font on">test</span>
			</div>
			<div class="wifi_right fr">
				<a href="javascript:void(0)" style="display: none;">{pigcms{$now_store.wifi_account}</a>
				<span href="javascript:void(0)" class="on">密码：{pigcms{$now_store.wifi_password}</span>
			</div>
		</section>
		</if>
		<section class="shopDetail">
            <if condition="$is_wap">
			<div class="linkInfo link-url" data-url="{pigcms{:U('Index/index',array('token'=>$now_store['mer_id']))}" onClick="turn('{pigcms{:U('Index/index',array('token'=>$now_store['mer_id']))}');">
				<div class="icon web">网</div>
				<div class="txt">商家微官网</div>
			</div>
            </if>
			<if condition="$now_store['have_shop'] eq 1">
				<php>$nows_shop_store = M('Merchant_store_shop')->where(array('store_id'=>$now_store['store_id']))->find();</php>
				<div class="linkInfo link-url" onClick="turn('{pigcms{:U('Shop/classic_shop')}&shop_id={pigcms{$now_store['store_id']}')" data-url="{pigcms{:U('Shop/classic_shop')}&shop_id={pigcms{$now_store['store_id']}" >
					<div class="icon" style="background:#F72530;">{pigcms{$config.shop_alias_name}</div>
					<div class="txt">{pigcms{$config.shop_alias_name}</div>
				</div>
			</if>
			<if condition="$config['pay_in_store']">
				<div class="linkInfo link-url" data-url="{pigcms{:U('My/pay',array('store_id' => $now_store['store_id']))}" onClick="turn('{pigcms{:U('My/pay',array('store_id' => $now_store['store_id']))}');">
					<div class="icon fu">付</div>
					<div class="txt">{pigcms{$config.cash_alias_name}</div>
				</div>
			</if>
			<if condition="$now_store['have_meal'] eq 1">
				<div class="linkInfo link-url" data-url="{pigcms{$now_store['wap_url']}" onClick="turn('{pigcms{$now_store['wap_url']}');">
					<div class="icon">{pigcms{$config.meal_alias_name}</div>
					<div class="txt">{pigcms{$config.meal_alias_name}</div>
				</div>
			</if>
			<script type="text/javascript">
				function turn(url){
					window.location=url;
				}
			</script>
		</section>
		<if condition="$coupon_list">
		<section class="Coupon">
			<div class="Coupon_top">
				优惠券
			</div>
			<div class="swiper-container swiper-container1">
				<div class="swiper-wrapper " id="coupon" >
					<volist name="coupon_list" id="vo">
						<div class="swiper-slide coupon" date-type ="{pigcms{$vo.coupon_id}"  data-wxcardid="{pigcms{$vo.wx_cardid}" data-tickettime="{pigcms{$vo.wx_ticket_addtime}" data-sign="{pigcms{$vo.cardsign}" data-status="{pigcms{$vo.status}" data-allow_new="{pigcms{$vo.allow_new}">

							<div class="Coupon_ntop fl">
								<div class="Coupon_ntop_span">
									<i>{pigcms{$config['Currency_symbol']}</i><em>{pigcms{$vo.discount|floatval}</em>
								</div>
								<div class="Coupon_ntop_span1">
									满{pigcms{$vo.order_money|floatval}{pigcms{$config['Currency_txt']}使用
								</div>
							</div>
							<div class="Coupon_nend fl">
								<div class="Coupon_ntop_span">
									{pigcms{$vo.name}
								</div>
								<div class="Coupon_ntop_span1">
									<p>使用时间</p>
									<p style="font-size:10px;">{pigcms{$vo.start_time|date='Y-m-d',###}至{pigcms{$vo.end_time|date='Y-m-d',###}</p>
								</div>
							</div>
							<div class="Coupon_Receive fr">立即领取</div>

						</div>
					</volist>
				</div>
			</div>
		</section>
		</if>
		<if condition="$store_group_list">
			<section class="storeProList introList">
				<div class="titleDiv"><div class="title">本店{pigcms{$config.group_alias_name}</div></div>
				<ul class="goodList">
					<volist name="store_group_list" id="vo">
						<li class="link-url" data-url="{pigcms{$vo.url}" <if condition="$i gt 2">style="display:none;"</if>>
							<php> if($vo['pin_num'] > 0){ </php><div class="pin_style"></div> <php> }  </php>
							<div class="dealcard-img imgbox">
								<img src="{pigcms{:thumb_img($vo['list_pic'], 276, 168, true)}" alt="{pigcms{$vo.group_name}"/>
							</div>
							<div class="dealcard-block-right" style="margin-left:94px;">
								<div class="title">{pigcms{$vo.group_name}</div>
								<div class="price">
									<strong>{pigcms{$config['Currency_symbol']}{pigcms{$vo['price']}</strong><if condition="$vo['wx_cheap']"><span class="tag">微信再减{pigcms{$config['Currency_symbol']}{pigcms{$vo['price']}{pigcms{$vo.wx_cheap}</span></if><span class="line-right">已售{pigcms{$vo['sale_count']+$vo['virtual_num']}</span>
								</div>
							</div>
						</li>
					</volist>
					<if condition="count($store_group_list) gt 2"><li class="more">全部展开</li></if>
				</ul>
			</section>
		</if>
		<if condition="$now_appoint">
			<section class="storeProList introList">
				<div class="titleDiv"><div class="title">本店预约</div></div>
				<ul class="goodList">
					<volist name="now_appoint" id="vo">
						<li  class="link-url" data-url="{pigcms{$vo.url}" <if condition="$i gt 2">style="display:none;"</if>>
							<div class="dealcard-img imgbox">
								<img src="{pigcms{$config.site_url}/index.php?c=Image&a=thumb&width=276&height=168&url={pigcms{:urlencode($vo['list_pic'])}" alt="{pigcms{$vo.appoint_name}"/>
							</div>
							<div class="dealcard-block-right">
								<div class="title">{pigcms{$vo.appoint_name}</div>
								<div class="price">
									<if condition='$vo["is_appoint_price"] neq 0'><strong>{pigcms{$vo['appoint_price']}</strong><span class="strong-color">{pigcms{$config['Currency_txt']}</span><else /><strong>面议</strong></if><span class="line-right">已预约{pigcms{$vo['appoint_sum']}</span>
								</div>
							</div>
						</li>
					</volist>
					<if condition="count($now_appoint) gt 2"><li class="more">全部展开</li></if>
				</ul>
			</section>
		</if>
		</section>
		<if condition="$now_store['supplier_info']">
		<section class="groom_dishes">
			<a class="dish_header clr" href="/wap.php?g=Wap&amp;c=Foodshop&amp;a=show_menu&amp;store_id=2"><b class="ft">商品简介</b></a>
			<div class="content_box">{pigcms{:replace_file_domain_content($now_store['supplier_info'])}</div>
		</section>
		</if>
		<!-- 新增html -->
		<if condition="$reply_list">
			<section class="details_evaluate">
				<div class="Moreinfor_top">评价</div>
				<ul style="padding-bottom:10px;">
					<volist name="reply_list" id="reply">
						<li>
							<div class="details_evaluate_top clr">
            <span class="img_box">
                <img src="{pigcms{$reply['avatar']}">
            </span>
								<div class="evaluate_left">
									<h3>{pigcms{$reply['nickname']}</h3>
									<span>{pigcms{$reply['add_time']}</span>
								</div>
								<div class="evaluate_right">
									<div class="atar_Show">
										<p></p>
									</div>
									<span><i>{pigcms{$reply['score']|floatval}</i>分</span>
								</div>
							</div>
							<div class="details_evaluate_end">{pigcms{$reply['comment']}</div>
							<if condition="$reply['merchant_reply_content']">
								<div class="details_evaluate_end" style="font-size:12px;background:#F8F8F8;padding:10px;"><span style="color:#FF532A;">商家回复：</span><span>{pigcms{$reply['merchant_reply_content']}</span></div>
							</if>
							<if condition="$reply['pics']">
								<div class="pics view_album" data-pics="<volist name="reply['pics']" id="voo">{pigcms{$voo.m_image}<if condition="count($reply['pics']) gt $i">,</if></volist>">
					<volist name="reply['pics']" id="voo">
						<span class="pic-container imgbox" style="background:none;"><img src="{pigcms{$voo.s_image}"/></span>&nbsp;
					</volist>
					</div>
		</if>
		</li>
		</volist>
		</ul>
		<!--暂时隐藏掉，后期再开发<if condition="$reply_count gt 20">
			<div class="stillmore">
				<a href="{pigcms{:U('Foodshop/reply', array('store_id' => $shop['store_id']))}" class="clr">
					<span>查看全部评价（{pigcms{$reply_count}）</span><em></em>
				</a>
			</div>
		</if>-->
		</section>
		</if>
	<script>
		<if condition="!$is_weixin_browser">
			var is_weixin_browser = false;
		<else />
		var is_weixin_browser = true;
		</if>
		var is_new =Number("{pigcms{$isnew}");
	</script>
	<script type="text/javascript">
		//图片横向滑动width
		var dish_length=$('.dish_content>a').length;
		$('.dish_content').width(dish_length*110);
		function close_swiper(){
			$('.albumContainer').remove();
		}
		//奖票滑动
		var myswiper5 = new Swiper('.swiper-container1', {direction : 'horizontal',  freeMode : true, freeModeMomentumRatio : 0.5, slidesPerView : 'auto'});
		$(function(){
			<if condition="count($wap_index_slider) gt 1">
				if($('.swiper-container2').size() > 0){
					var mySwiper2 = $('.swiper-container2').swiper({
						pagination:'.swiper-pagination2',
						loop:true,
						grabCursor: true,
						paginationClickable: true,
						simulateTouch:false
					});
				}
			</if>

			$(".m10_list").each(function(){
				var height = $(this).height();
				if (height > 221) {
					$(this).css({"height":"221px","overflow":"hidden"})
				} else {
					$(this).find(".more").hide();
				}
			});
			$(".m10_list .more").click(function(){
				$(this).hide();
				$(this).parents(".m10_list").css("height","auto");
			});
			$(".m10_list .more i").text(parseFloat($(".m10_list dd").length) - 2);

			// 清除边框
			$(".navBox_list dl").each(function(){
				$(this).find("dd.Menulink").last().css("border-bottom","none");
			});

			$('#coupon .coupon ').click(function(event) {
				var tmp= $(this);
				$.ajax({
					url: "{pigcms{:U('Systemcoupon/ajax_check_login')}",
					type: 'POST',
					dataType: 'json',
					data: {isnew:$(tmp).data('allow_new')},
					success:function(data){


						if(data.error_code>0){
							layer.open({
								content: data.msg
								,btn: ['我知道了']
							});

							if(data.error_code==1){

								if(!is_weixin_browser){
									window.location.href="{pigcms{:U('Login/index')}";
								}else{
									window.location.href="{pigcms{:U('My/index')}";
								}
							}
						}else{


							if($(this).data('wxcardid')!=''&&tmp.data('status')==1 && is_weixin_browser){
								layer.open({
									title:[
										'是否同步到微信优惠券',
										'background-color:#8DCE16; color:#fff;'
									],
									content: '同步微信优惠券，您可以在微信卡包中查看'
									,btn: ['是', '否']
									,yes: function(index){
										var cardlist = [];
										var i = 0;

										var wxcardid = $(tmp).data('wxcardid');
										var sign = $(tmp).data('sign');
										var tickettime = $(tmp).data('tickettime');
										var status = $(tmp).data('status');
										var allow_new = Number($(tmp).data('allow_new'));
										if(wxcardid!=''&&status==1&&(is_new&&allow_new||!allow_new)){
											cardlist.push({'cardId':wxcardid,'cardExt':'{"code": "", "openid": "", "timestamp":"'+tickettime+'","signature":"'+sign+'"}'});
										}

										if(cardlist){
											wx.addCard({
												cardList:
												cardlist
												,
												success: function (res) {
													layer.open({
														content: '已成功同步到微信卡包'
														,btn: ['我知道了']
													});

													window.location.reload();
												}
											});
										}
									}
									,no:function(index){
										ajax_had_pull(tmp)
									}
								});

							}else{
								ajax_had_pull(tmp)
							}
						}
					}
				});


			});

			$('.headshop,.pics').click(function(){
				var album_more = $(this).data('pics');
				if (album_more == '') return false;
				var album_array = album_more.split(',');
				if(/(micromessenger)/.test(navigator.userAgent.toLowerCase())){
					wx.previewImage({
						current:album_array[0],
						urls:album_array
					});
				}else{
					var album_html = '<div class="albumContainer" style="display:block;">';
					album_html += '<div class="swiper-container">';
					album_html += '<div class="swiper-wrapper">';
					$.each(album_array,function(i,item){
						album_html += '<div class="swiper-slide">';
						album_html += '<img src="'+item+'"/>';
						album_html += '</div>';
					});
					album_html += '</div>';
					album_html += '<div class="swiper-pagination"></div><div class="swiper-close" onclick="close_swiper()">X</div>';
					album_html += '</div>';
					album_html += '</div>';
					$('body').append(album_html);
					mySwiper_big = $('.albumContainer .swiper-container').swiper({
						pagination:'.albumContainer .swiper-pagination',
						loop:true,
						grabCursor: true,
						paginationClickable: true
					});
				}
			});

			$(".evaluate_right").each(function() {
				$(this).find("p").css("width", parseFloat($(this).find("i").text()) * 18);
			});
		});
		function ajax_had_pull(tmp){
			$.ajax({
				url: "{pigcms{:U('My_card/had_pull')}",
				type: 'POST',
				dataType: 'json',
				data: {coupon_id:tmp.attr('date-type')},
				success:function(data){
					if(data.error_code){
						if(data.error_code == 1 || data.error_code==2){
							layer.open({
								content: data.msg
								,btn: ['我知道了']
							});
							window.location.reload();
						}else if(data.error_code==3){
							layer.open({
								content: data.msg
								,btn: ['我知道了']
							});
							tmp.find('.rob').html('<div class="Already Alreadyon"></div>');
							tmp.find('.right').addClass('rightEnd');
							tmp.find('.collar').html('已抢光');
						}else {
							layer.open({
								content: data.msg
								,btn: ['我知道了']
							});

						}
					}else{
						tmp.find('.rob').html('<div class="Already"></div>');
						tmp.find('.right').addClass('rightAl');
						tmp.find('.collar').html('已领取');

						layer.open({
							content: data.msg
							,btn: ['我知道了']
						});
					}
				}
			});
		}
	</script>
		<script type="text/javascript">
		window.shareData = {
					"moduleName":"Merchant",
					"moduleID":"0",
					"imgUrl": "{pigcms{$now_store.all_pic.0}",
					"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Merchant/shop', array('store_id' => $now_store['store_id']))}",
					"tTitle": "{pigcms{$now_store.name}",
					"tContent": ""
		};
		</script>
		<script>
			function turn(url){
				window.location.href=url;
			}
		</script>
		{pigcms{$shareScript}
		<include file="kefu" />
	</body>
</html>