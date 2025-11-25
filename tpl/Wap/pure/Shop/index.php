<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN"> 
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$config.shop_alias_name}</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link href="{pigcms{$static_path}css/groupBuy_index.css" rel="stylesheet"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}shop/css/shopBase.css?t={pigcms{$_SERVER.REQUEST_TIME}"/>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/jquery.lazyload.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?220" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/idangerous.swiper.min.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
        <if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
            <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places&key={pigcms{$config.google_map_ak}"></script>
            <script type="text/javascript">var is_google_map = "{pigcms{$config.google_map_ak}"</script>
            <else />
            <script type="text/javascript" src="{pigcms{$_SERVER.REQUEST_SCHEME}://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
        </if>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?t={pigcms{$_SERVER.REQUEST_TIME}" charset="utf-8"></script>
		<script src="https://cdn.jsdelivr.net/hls.js/latest/hls.min.js"></script>
		<script type="text/javascript">
		var theme_ = 'index', deliverName = "{pigcms{$config['deliver_name']}";
		<if condition="$user_long_lat">var last_user_long = "{pigcms{$user_long_lat.long}",last_user_lat = "{pigcms{$user_long_lat.lat}";<else/>var last_user_long = '0',last_user_lat  = '0';</if>
		var open_extra_price =Number("{pigcms{$config.open_extra_price}"); var nowIndex = '{pigcms{$nowIndex}', cartid = '{pigcms{$cartid}', spellfrm = '{pigcms{$_GET["frm"]}';
		var spell_add_cart = '{pigcms{$config.site_url}/wap.php?c=Shop&a=addNew', spell_cart_url = '{pigcms{$config.site_url}/wap.php?c=Shop&a=';
		var user_long = '0',user_lat  = '0';var user_address='';var ajax_url_root = "{pigcms{$config.site_url}/wap.php?c=Shop&a=";var ajax_map_url_google = "{pigcms{$config.site_url}/index.php?g=Index&c=Map&a=";var sync_url = "{pigcms{$config.site_url}/wap.php?c=Shop&a=sync";var check_cart_url = "{pigcms{$config.site_url}/wap.php?c=Shop&a=confirm_order";var ajax_map_url = "{pigcms{$config.site_url}/index.php?g=Index&c=Map&a=suggestion&city_id={pigcms{$config.now_city}";var get_route_url = "{pigcms{:U('Group/get_route')}";var baiduToGcj02Url = "{pigcms{:U('Userlonglat/baiduToGcj02')}";var city_id="{pigcms{$config.now_city}";var ajax_cart_url = "{pigcms{$config.site_url}/wap.php?c=Shop&a=ajax_cart";var ajax_cart_save_url = "{pigcms{$config.site_url}/wap.php?c=Shop&a=ajax_cart_save";var cat_url="",sort_url="",type_url="";var noAnimate= true;var userOpenid="{pigcms{$_SESSION.openid}";var shopShareUrl = "{pigcms{$config.site_url}{pigcms{:U('Shop/index',array('openid'=>$_SESSION['openid'], 'type'=>'share'))}&shop-id=",shopReplyUrl = "{pigcms{$config.site_url}/index.php??g=Index&c=Reply&a=ajax_get_list&order_type=3&parent_id=", deliverExtraPrice = 0;            var ajax_basic_price = "{pigcms{$config.site_url}/wap.php?c=Shop&a=ajax_basic_price";
		var currency_txt  ='{pigcms{$config['Currency_txt']}';
		var currency_symbol  ='{pigcms{$config['Currency_symbol']}';

		var currency_symbol = "{pigcms{$config.Currency_symbol}";

		</script>
        <script type="text/javascript" src="{pigcms{$static_path}shop/js/shopBase.js?t={pigcms{$_SERVER.REQUEST_TIME}" charset="utf-8"></script>
        <if condition="$config['is_open_jd_goods'] eq 1">
            <link rel="stylesheet" type="text/css" href="http://misc.360buyimg.com/product/skin/2012/pshow.css?t=20120717.css" media="all" />
        </if>
        <style type="text/css">
        	.banner{padding-top: 0;}
        </style>
	</head><!--iss-->
	<body>
    <div id = 'hidden_map' class="display:none"></div>
		<div id="pageList" class="pageDiv" <if condition="$config['shop_show_footer']">style="padding-bottom:56px;"</if>>
			<section id="listHeader" class="roundBg">
				<div id="listBackBtn" class="listBackBtn hide"><div></div></div>
				<div id="locationBtn" class="page-link" data-url="address" data-url-type="openRightFloatWindow">
					<span class="location"></span>
					<span id="locationText">正在定位</span>
					<span class="go"></span>
				</div>
				<div id="searchBtn" class="listSearchBtn page-link" data-url="shopSearch"><div></div></div>
			</section>
			<section id="listBanner" class="banner">
				<div class="swiper-container swiper-container1">
					<div class="swiper-wrapper"></div>
					<div class="swiper-pagination swiper-pagination1"></div>
				</div>
			</section>
			<section id="listSlider" class="slider">
				<div class="swiper-container swiper-container2" style="height:178px;">
					<div class="swiper-wrapper"></div>
					<div class="swiper-pagination swiper-pagination2"></div>
				</div>
			</section>
<!--	<div>111111111</div>-->
	<!--5-->
	<php> if($config['shop_main_page_show_ad']==1){</php>
    <if condition="(abs($config['shop_main_page_center_type']) eq 5) AND !empty($slider_list)">
		<section class="cityRun">
			<div class="cityItem clear <if condition="$config.shop_main_page_show_type eq 0">acss</if>">
	<div class="item0 clear ft link-url" data-url="{pigcms{$slider_list[0]['url']}">
		<if condition="$config.shop_main_page_show_type eq 1">
			<div class="itemLeft ft fire" style='width:100%'>
				<h3>{pigcms{$slider_list[0]['name']}</h3>
				<h4 class="lan">{pigcms{$slider_list[0]['sub_name']} </h4>

			</div>
			<div class="itemRight ft fire" style='width:100%'>
				<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[0]['pic']}" style='width:100%;height:100%'/>
			</div>
			<else />
			<div class="itemRight ft fire" style='width:100%'>
				<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[0]['pic']}" style='width:100%;height:100%'/>
			</div>

		</if>
	</div>
	<div class="item0 ft link-url" data-url="{pigcms{$slider_list[1]['url']}">
		<if condition="$config.shop_main_page_show_type eq 1">
			<div class="itemLeft ft fire" style='width:100%'>
				<h3>{pigcms{$slider_list[1]['name']}</h3>
				<h4 class="lan">{pigcms{$slider_list[1]['sub_name']} </h4>

			</div>
			<div class="itemRight ft fire" style='width:100%'>
				<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[1]['pic']}" style='width:100%;height:100%'/>
			</div>
			<else />
			<div class="itemRight ft fire" style='width:100%'>
				<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[1]['pic']}" style='width:100%;height:100%'/>
			</div>

		</if>
	</div>
	</div>
	<div class="cityTheck" >
		<ul class="clear" style='display:flex;'>
			<li class="ft link-url" data-url="{pigcms{$slider_list[2]['url']}" style='padding:0px;flex:1'>
				<if condition="$config.shop_main_page_show_type eq 1">
					<h3>{pigcms{$slider_list[2]['name']}</h3>
					<h4 class="c1">{pigcms{$slider_list[2]['sub_name']}</h4>
					<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[2]['pic']}" class='four ' style='width:100%'/ >
					<else />
					<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[2]['pic']}" class='four fire-bottom'style='width:100%'/>
				</if>
			</li>
			<li class="ft link-url" data-url="{pigcms{$slider_list[3]['url']}" style='padding:0px;flex:1'>
				<if condition="$config.shop_main_page_show_type eq 1">
					<h3>{pigcms{$slider_list[3]['name']}</h3>
					<h4 class="c1">{pigcms{$slider_list[3]['sub_name']}</h4>
					<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[3]['pic']}" class='four' style='width:100%'/>
					<else />
					<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[3]['pic']}" class='four fire-bottom' style='width:100%'/>
				</if>
			</li>
			<li class="ft link-url" data-url="{pigcms{$slider_list[4]['url']}" style='padding:0px;flex:1'>
				<if condition="$config.shop_main_page_show_type eq 1">
					<h3>{pigcms{$slider_list[4]['name']}</h3>
					<h4 class="c1">{pigcms{$slider_list[4]['sub_name']}</h4>
					<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[4]['pic']}" class='four' style='width:100%'/>
					<else />
					<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[4]['pic']}" class='four fire-bottom' style='width:100%'/>
				</if>
			</li>
		</ul>
	</div>
	</section>

	<elseif condition="abs($config['shop_main_page_center_type']) eq 1" />
	<!--1-->
	<section class="run clear  <if condition="$config.group_main_page_show_type eq 0">adds</if>">
	<div class="ft acc link-url" data-url="{pigcms{$slider_list[0]['url']}" style="width:100%" >
		<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[0]['pic']}" class="slideimg1" style='flex:1;width: 100%;'/>
	</div>
	</section>

	<elseif condition="abs($config['shop_main_page_center_type']) eq 2" />
	<!--2-->
	<section class="run clear <if condition="$config.shop_main_page_show_type eq 0">adds</if>">
	<div class="runItem ft add ">
		<div class="itemTop">
			<ul class="clear link-url" data-url="{pigcms{$slider_list[0]['url']}">
				<li class="ft shree">
					<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[0]['pic']}"/>
				</li>
			</ul>
		</div>
	</div>
	<div class="runItem ft">
		<div class="itemTop">
			<ul class="clear link-url" data-url="{pigcms{$slider_list[1]['url']}">
				<li class="ft shree">
					<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[1]['pic']}"/>
				</li>
			</ul>
		</div>
	</div>
	</section>

	<elseif condition="abs($config['shop_main_page_center_type']) eq 3" />
	<!--3-->
    <notempty name="$slider_list">
	<section class="run clear <if condition="$config.shop_main_page_show_type eq 0">adds</if>" style='display:flex;'>
	<div class="runItem ft acc link-url" data-url="{pigcms{$slider_list[0]['url']}" style='padding-top:0px;display:flex;flex-direction: column'>
		<if condition="$config.shop_main_page_show_type eq 1">
			<h3 >{pigcms{$slider_list[0]['name']}</h3>
			<h4>{pigcms{$slider_list[0]['sub_name']} </h4>
			<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[0]['pic']}" style='width: 100%; flex:1;max-height:200px'/>
			<else />
			<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[0]['pic']}" style='flex:1;width: 100%;height:190px;'/>
		</if>
	</div>
	<div class="runItem ft" style='display: flex; flex-direction: column;'>
		<div class="itemTop" style='flex:1;'>
			<ul class="clear link-url" data-url="{pigcms{$slider_list[1]['url']}">
				<if condition="$config.shop_main_page_show_type eq 1">
					<li class="ft">
						<h3 class='hiddens'>{pigcms{$slider_list[1]['name']}</h3>
						<h4>{pigcms{$slider_list[1]['sub_name']} </h4>

					</li>
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[1]['pic']}"/>
					</li>
					<else />
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[1]['pic']}"/>
					</li>
				</if>
			</ul>
		</div>
		<div class="itemBottom" style='flex:1'>
			<ul class="clear link-url" data-url="{pigcms{$slider_list[2]['url']}">
				<if condition="$config.shop_main_page_show_type eq 1">
					<li class="ft">
						<h3>{pigcms{$slider_list[2]['name']}</h3>
						<h4>{pigcms{$slider_list[2]['sub_name']} </h4>

					</li>
					<li class="ft">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[2]['pic']}"/>
					</li>
					<else />
					<li class="ft">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[2]['pic']}"/>
					</li>
				</if>
			</ul>
		</div>
	</div>
	</section>
    </notempty>
	<elseif condition="abs($config['shop_main_page_center_type']) eq 4" />
	<!--同城跑腿3-->
	<section class="run clear <if condition="$config.shop_main_page_show_type eq 0">adds</if>">
	<div class="runItem ft add ">
		<div class="itemTop">
			<ul class="clear link-url" data-url="{pigcms{$slider_list[0]['url']}">
				<if condition="$config.shop_main_page_show_type eq 1">
					<li class="ft shree">
						<h3 >{pigcms{$slider_list[0]['name']}</h3>
						<h4>{pigcms{$slider_list[0]['sub_name']} </h4>

					</li>
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[0]['pic']}"/>
					</li>
					<else />
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[0]['pic']}"/>
					</li>
				</if>

			</ul>
		</div>
		<div class="itemBottom">
			<ul class="clear link-url" data-url="{pigcms{$slider_list[1]['url']}">
				<if condition="$config.shop_main_page_show_type eq 1">
					<li class="ft shree">
						<h3>{pigcms{$slider_list[0]['name']}</h3>
						<h4>{pigcms{$slider_list[0]['sub_name']} </h4>

					</li>
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[1]['pic']}"/>
					</li>
					<else />
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[1]['pic']}"/>
					</li>
				</if>
			</ul>
		</div>
	</div>

	<div class="runItem ft">
		<div class="itemTop">
			<ul class="clear link-url" data-url="{pigcms{$slider_list[2]['url']}">
				<if condition="$config.shop_main_page_show_type eq 1">
					<li class="ft shree">
						<h3>{pigcms{$slider_list[2]['name']}</h3>
						<h4>{pigcms{$slider_list[2]['sub_name']} </h4>
					</li>
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[2]['pic']}"/>
					</li>
					<else />
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[2]['pic']}"/>
					</li>
				</if>
			</ul>
		</div>
		<div class="itemBottom">
			<ul class="clear link-url" data-url="{pigcms{$slider_list[3]['url']}">
				<if condition="$config.shop_main_page_show_type eq 1">
					<li class="ft shree">
						<h3>{pigcms{$slider_list[3]['name']}</h3>
						<h4>{pigcms{$slider_list[3]['sub_name']} </h4>

					</li>
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[3]['pic']}"/>
					</li>
					<else />
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[3]['pic']}"/>
					</li>
				</if>
			</ul>
		</div>
	</div>
	</section>
	</if>
	<php>}</php>
	<div style="margin-top: 6px;"></div>
	<section class="run clear adds">
		<volist name="adverMiddleImg" id="middle">
			<div class="ft acc link-url" data-url="{pigcms{$middle['url']}" style="width:100%;height:103px" >
				<img src="{pigcms{$middle['pic']}" class="slideimg1" style='flex:1;width: 100%;height:103px'/>
			</div>
		</volist>
	</section>
			<section id="listRecommend" class="recommend"></section>
			<section id="listNavBox" class="navBox">
				<ul>
					<li class="dropdown-toggle caret category" data-nav="category">
						<span class="nav-head-name">店铺分类</span>
					</li>
					<li class="dropdown-toggle caret sort" data-nav="sort">
						<span class="nav-head-name">智能排序</span>
					</li>
					<li class="dropdown-toggle caret type subway" data-nav="type">
						<span class="nav-head-name">类型</span>
					</li>
				</ul>
				<div class="dropdown-wrapper category">
					<div class="dropdown-module">
						<div class="scroller-wrapper">
							<div id="dropdown_scroller" class="dropdown-scroller">
								<div>
									<ul>
										<li class="category-wrapper" style="min-height:200px;">
											<ul class="dropdown-list"></ul>
										</li>
										<li class="sort-wrapper">
											<ul class="dropdown-list"></ul>
										</li>
										<li class="type-wrapper">
											<ul class="dropdown-list"></ul>
										</li>
									</ul>
								</div>
							</div>
							<div id="dropdown_sub_scroller" class="dropdown-sub-scroller"><div></div></div>
						</div>
					</div>
				</div>
			</section>
			<section id="listNavPlaceHolderBox">
			</section>
			<section id="storeList">
				<dl class="dealcard"></dl>
				<div id="storeListLoadTip">正在加载中...</div>
			</section>
			<section class="shade"></section>
			<php>if(!$config['shop_show_footer']){$no_footer = true;$no_small_footer = true;}</php>
			<include file="Public:footer"/>
		</div>
		<div id="pageShop" class="pageDiv">
			<section id="shopBanner" style="padding-top:10px;margin-top:0px;">
				<div class="leftIco" style="top:13px;">
					<div id="shopIcon" style="width:auto;height:auto;border-radius: unset;border: unset;margin-left:6px;background-size:104px 58px;background-position-x:-23px;">
						<img src='' style='    border-radius: 22px;'/>
					</div>
				</div>
				<div class="text">
					<div id="shopTitle" style="margin-left:0;font-size:16px;"></div>
					<div id="deliveryText" style="height:20px;line-height:20px;"></div>
					<div id="shopNoticeText" style="height:20px;line-height:20px;"></div>
				</div>
				<div class="discount">
					<div class="noticeBox"><div class="notice"><div></div></div></div>
					<span id="shopCouponText"></span>
				</div>
				<div id="homeStore">
					<span></span>
				</div>
				<div id="ScanStore">
					<span></span>
				</div>
				<!-- <div class="listSearchBtn page-link" data-url="goodsSearch" style="right: -3px;"><div></div></div> -->
			</section>
			<section id="shopMenuBar">
				<ul>
					<li class="caret product active" data-nav="product">商品</li>
					<li class="caret reply" data-nav="reply">评价</li>
					<li class="caret merchant" data-nav="merchant">商家</li>
				</ul>
			</section>
			
			<section id="shopCatBar" style="display:none;">	
				<div class="title">
					全部分类
				</div>
				<div class="content" style="overflow-y: auto;">
					<ul></ul>
				</div>
			</section>
			<section id="shopContentBar">
				<div id="shopProductBox">
					<div id="shopProductBottomBar"><ul class="clearfix"></ul><div id="shopProductBottomLine"></div></div>
					<div id="shopProductLeftBar"><dl></dl></div>
					<div id="shopProductRightBar"><dl></dl></div>
					<div id="shopProductCartShade"></div>
					<div id="top_fei" style="position: fixed;background-color: white;width: 100%;bottom: 50px;z-index: 126;overflow-y:auto; display: none;">
						<div class="top_header" style="background: rgba(6, 193, 174, 0.31);text-align:center;color: #393A3A;width: 100%;height: 30px;line-height: 30px;">加<span style="color:#CB0003;" class="header_data">3</span>{pigcms{$config['Currency_txt']}就能配送 <span style="color:#CB0003;">[确认加钱]</span></div>
						<div id="shopProductCartBox"></div>
					</div>
					<div id="shopProductCart">
						<div id="cartInfo" class="cartLeft" style="display:none;">
							<div class="cart">
								<div id="cartNumber">0</div>
							</div>
							<div class="price"><dl><dt>共{pigcms{$config['Currency_symbol']}<span id="cartMoney">0</span></dt><dd style="font-size: 12px;color: #979796;">另需起送价附加费<span id="additional">3</span>{pigcms{$config['Currency_txt']}</dd></dl></div>
						</div>
						<div id="emptyCart">
							<div class="cart"></div>购物车是空的
						</div>
						<div id="checkCart" style="display:none;">选好了</div>
						<div id="checkCartEmpty">{pigcms{$config['Currency_symbol']}0起送价</div>
					</div>
				</div>
				<div id="shopReplyBox" style="display:none">
					<!-- 店铺总评分 -->
					<div class="usats">
						<div class="clear itemLeft">
							<h2 class="replyScore">0</h2>
							<div class="">
								<ul>
									<li class="fen">
										<span><b></b></span>
									</li>
									<li>店铺综合评分  共<span id="replyCount">0</span>人评价</li>
								</dl>
							</div>
						</div>
						<div class="itemRight">
							<ul>
								<li id="replyDeliverScore">0</li>
								<li>配送评分</li>
							</ul>
						</div>
					</div>
					<div id="shopReplyDiv">
						<ul class="clearfix">
							<li class="active" data-tab="">全部(<em>0</em>)</li>
							<li data-tab="good">满意(<em>0</em>)</li>
							<li data-tab="wrong">不满意(<em>0</em>)</li>
						</ul>
						<dl></dl>
						<div id="noReply">暂无评价</div>
						<div id="showMoreReply">加载更多</div>
					</div>
				</div>
				<div id="shopMerchantBox">
					<dl id="shopMerchantDescBox">
						<dd class="merchant more link-url">商家官网</dd>
						<dd class="phone more">店铺电话</dd>
						<dd class="address more page-link"><span></span>店铺地址</dd>
						<dd class="openTime">营业时间</dd>
						<dd class="deliveryType">配送服务</dd>
						<dd class="merchantNotice">店铺公告</dd>
						
					</dl>
					<if condition="!$merchant_link_showOther">
						<dl id="shopMerchantLinkBox">
							<dd class="more link-url" data-url="{pigcms{:U('My/shop_order_list')}"><span></span>我的{pigcms{$config.shop_alias_name}订单</dd>
						</dl>
					</if>
					<dl id="shopMerchantCouponBox">
						<dd>配送服务</dd>
						<dd>配送时间</dd>
					</dl>
					
					<div class="photo_s">
						<div class="photo">
							<h2>商家照片</h2>
							<ul class="clearfix store_image" data-pics=""></ul>
						</div>
						<div class="photo">
							<h2>资质证照</h2>
							<ul class="clearfix auth_file_image" data-pics=""></ul>
						</div>
					</div>
				</div>
				<div id="shopPageShade"></div>
				<div id="shopPageCatShade"></div>
				<div id="shopDetailPage" style="display:none;">
					<div class-s="scrollerBox">
						<div id="shopDetailpageClose" class="closeBtn"><div></div></div>
						<div id="shopDetailPageImgbox" class="swiper-container swiper-container-productImg">
							<div class="swiper-wrapper"></div>
							<div class="swiper-pagination swiper-pagination-productImg"></div>
						</div>
						<div id="shopDetailPageTitle">
							<div class="title">商品名称</div>
							<div class="desc">商品描述</div>
						</div>
						<div id="shopDetailPageFormat">商品库存</div>
						<div id="shopDetailPageBar" class="clearfix">
							<div class="fl" id="shopDetailPagePrice">价格</div>
							<div class="fr">
								<div id="shopDetailPageBuy">加入购物车</div>
								<div id="shopDetailPageNumber" style="display:none;">
									<div class="product_btn plus"></div>
									<div class="product_btn number">0</div>
									<div class="product_btn min"></div>
								</div>
							</div>
						</div>
						<div id="shopDetailPageLabel">
							<div class="tip">我要备注<div class="question"></div></div>
							<div id="shopDetailPageLabelBox"></div>
						</div>
						<div id="shopDetailPageContent">
							<div class="title">商品描述</div>
							<div class="content">商品描述内容</div>
						</div>
					</div>
				</div>
			</section>
		</div>
		<div id="pageMap" class="pageDiv">
			<div id="shopDetailMapClose" class="closeBtn"><div></div></div>
			<div id="shopDetailMapBiz"></div>
			<div id="shopDetailMapBar">
				<span id="shopDetailMapAddress">地址</span>
				<a class="btn " id="shopDetailMapAddressGo">查看路线</a>
			</div>
		</div>
		<div id="pageCat" class="pageDiv">
			<section id="catHeader"  <if condition='$config["mobile_head_color"]'>style="background-color: {pigcms{$config["mobile_head_color"]};"</if>>
				<div id="catBackBtn" class="backBtn"></div>
				<span id="catTitle">分类</span>
				<div id="catSearchBtn" class="listSearchBtn page-link" data-url="shopSearch"><div></div></div>
			</section>
			<div id="pageCatNav"></div>
			<section class="shade"></section>
			<section id="storeList">
				<dl class="dealcard"></dl>
				<div id="storeListLoadTip">正在加载中...</div>
			</section>
		</div>
		<div id="pageLoadTipShade" class="pageLoadTipBg">
			<div id="pageLoadTipBox" class="pageLoadTipBox">
				<div class="pageLoadTipLoader">
					<div style="background-image:url({pigcms{$config.shop_load_bg});"><!--img src="{pigcms{$static_path}shop/images/pageTipImg.png"/--></div>
				</div>
			</div>
		</div>
		<div id="pageAddress" class="pageDiv">
			<div id="pageAddressHeader" class="searchHeader">
				<div id="pageAddressBackBtn" class="searhBackBtn"></div>
				<div id="pageAddressSearch" class="searchBox">
					<div class="searchIco"></div>
					<input type="text" id="pageAddressSearchTxt" class="searchTxt" placeholder="请输入收货地址" autocomplete="off"/>
					<div class="delIco" id="pageAddressSearchDel"><div></div></div>
				</div>
				<div id="pageAddressSearchBtn" class="searchBtn">搜索</div>
			</div>
			<div id="pageAddressContent" class="searchAddressList">
				<div id="pageAddressLocationList">
					<div class="title">当前地址</div>
					<dl class="content">
						<dd data-long="" data-lat="" data-name="">
							<div class="name"></div>
						</dd>
					</dl>
				</div>
				<div id="pageAddressUserList">
					<div class="title">我的收货地址</div>
					<dl class="content"></dl>
				</div>
			</div>
			<div id="pageAddressSearchContent" class="searchAddressList" style="display:none;">
				<dl class="content"></dl>
			</div>
		</div>
		<div id="pageShopSearch" class="pageDiv">
			<div id="pageShopSearchHeader" class="searchHeader">
				<div id="pageShopSearchBackBtn" class="searhBackBtn"></div>
				<div id="pageShopSearchBox" class="searchBox">
					<div class="searchIco"></div>
					<input type="text" id="pageShopSearchTxt" class="searchTxt" placeholder="请输入店铺名称" autocomplete="off"/>
					<div class="delIco" id="pageShopSearchDel"><div></div></div>
				</div>
				<div id="pageShopSearchBtn" class="searchBtn">搜索</div>
			</div>
			<div id="storeList" style="display:none;">
				<dl class="dealcard"></dl>
				<div id="storeListLoadTip">正在加载中...</div>
			</div>
		</div>
        
		<!-- <div id="pageGoodsSearch" class="pageDiv">
            <div id="pageGoodsSearchHeader" class="searchHeader">
                <div id="pageGoodsSearchBackBtn" class="searhBackBtn"></div>
                <div id="pageGoodsSearchBox" class="searchBox">
                    <div class="searchIco"></div>
                    <input type="text" id="pageGoodsSearchTxt" class="searchTxt" placeholder="请输入商品名称" autocomplete="off"/>
                    <div class="delIco" id="pageGoodsSearchDel"><div></div></div>
                </div>
                <div id="pageGoodsSearchBtn" class="searchBtn">搜索</div>
            </div>
            <div id="goodsList" style="display:none;">
                <dl class="dealcard"></dl>
<!--                 <div id="storeListLoadTip">正在加载中...</div> -->
            </div>
        </div>
        
		<div id="pwd_bg" class="bg bg_style video" style="height: 921px;"></div>
		<div id="store_live" class="store_live video">
			<video controls="true" width="100%" height="100%" webkit-playsinline style="object-fit:fill"  id="video_url"></video>
		</div>
		<div class="cd-top">Top</div>
	<div class="coupon_mask cursor" style="display:none"></div>
	<div class='explainModel' style="display:none">
		<div class='explainContent'>
			<p>结算说明</p>
			<div>
                该商品为计重类商品，如果您下单后商品实际重量小于购买重量，差价部分在订单完成后会直接退回到您平台余额账户中；如果您下单商品实际重量大于购买重量，则有平台贴补差额，您不需要再次支付。
			</div>
			<div  style='    padding-bottom: 10px;'>
				<a class='explainBtn' href='javascript:;'>知道了</a>
			</div>
		</div>
	</div>
		<include file="Shop:js_theme"/>
        <include file="kefu" />
        <!-- 搜索框 -->
			<!-- <div id="secrahShow" data-url="goodsSearch" style="display: none;">
				<span></span>
			</div> -->
			<!-- 扫一扫 -->
			<!--div id="ScanStore">
				<span></span>
			</div-->
     <!--    <div id="searchFloor" >
			<div id="pageShopSearchHeader" class="searchHeader">
				<div id="pageShopSearchBackBtn66" class="searhBackBtn"></div>
				<div id="pageShopSearchBox" class="searchBox">
					<div class="searchIco"></div>
					<input type="text" id="pageShopSearchTxt" class="searchTxt" placeholder="请输入店铺名称" autocomplete="off"/>
					<div class="delIco" id="pageShopSearchDel"><div></div></div>
				</div>
				<div id="pageShopSearchBtn" class="searchBtn">搜索</div>

			</div> -->
			<!-- <div id="storeList" >
					<dl class="dealcard"></dl>
					<div id="storeListLoadTip">正在加载中...</div>
				</div> 
				<div class="addHtml"></div>
		</div-->
		<script>
				
			     
		   //    /* 绑定鼠标左键按住事件 */
		   //    $("#secrahShow").on('touchstart',function(e){  
			  //     	var height1=$(window).height();
			      	
			  //       /* 获取需要拖动节点的坐标 */
			  //       var offset_y = $(this)[0].offsetTop;//y坐标
			       
			  //       /* 获取当前鼠标的坐标 */
			  //       var mouse_y = e.originalEvent.targetTouches[0].pageY;
			  //        //console.log(mouse_y);
			  //       /* 绑定拖动事件 */
			  //       /* 由于拖动时，可能鼠标会移出元素，所以应该使用全局（document）元素 */
			  //       $("#secrahShow").bind("touchmove",function(ev){
			        	
			  //         /* 计算鼠标移动了的位置 */
			  //         var _y = ev.originalEvent.targetTouches[0].pageY - mouse_y;
			  //         /* 设置移动后的元素坐标 */

			  //         var now_y = (offset_y + _y );
			  //        if(now_y<0){
			  //        	now_y=(now_y+40)+'px';
			  //        }else if((now_y+40+50)>=height1){
			  //        	now_y=(now_y-90)+"px";
			  //        }else{
			  //        	now_y=now_y+'px';
			  //        } 
					// /* 改变目标元素的位置 */
			  //         $('#secrahShow').css({
			  //           top:now_y
			  //           //left:now_x
			  //         });
			         
			          
		   //      });
		   //    });
		   $('#shopCatBar .content').css('max-height', $(window).height() - 152 - 66)
		   $(document).on("click",".instruction a",function(){
			   $(".coupon_mask").show()
			   $(".explainModel").show()

			   $(".explainContent").addClass("active")
		   })
		   $(document).on("click",".explainBtn",function(){
			   $(".coupon_mask").hide();
			   $(".explainContent").removeClass("active")
		   })
           $(document).on("click",".coupon_mask",function(){
               $(".coupon_mask").hide();
               $(".explainContent").removeClass("active")
           })
		    $(function(){
		      	if (window.location.href.indexOf('share') != -1) {
					$('#homeStore').show()
				} else {
					$('#homeStore').hide()
				}
				$(document).on('click', '#homeStore', function(){
					window.location.href = "{pigcms{$config.site_url}{pigcms{:U('Shop/index')}";
					return false;
				})
		      })
		</script>
		<script type="text/javascript">
			window.shareData = {
				"moduleName":"Shop",
				"moduleID":"0",
				"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>", 
				"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Shop/index')}",
				"tTitle": "{pigcms{$config.shop_alias_name} - {pigcms{$config.site_name}",
				"tContent": "{pigcms{$config.seo_description}"
			};


			$(function(){
				$('#container').css({top:'50px'});
				var Slide1_height=($(window).width() - 20) * 181 / 696
				$(".slideimg1").css('height',Slide1_height);
				
				if ($(window).width() <= 320) {
					$('.shree').css("height",'115px');
					$(".fire").css("height",'95px');
					$(".four").css('height','65px' )
				}else if( 320<$(window).width() <= 375){
					$('.shree').css("height",'125px');
					$(".fire").css("height",'109px');
					$(".four").css('height','75px' )
				}else{
					$('.shree').css("height",'133px');
					$(".fire").css("height",'120px');
					$(".four").css('height','85px' )
				}
				
				// 三图 左280*270 右280*135  四图 350*240 五图 上350*240 下230*260
				$(".fire").css("height", (($(window).width() - 20) / 2 - 2.5) * 240 / 350 );
				$(".fire-bottom").css("height", $(window).width() / 3 * 260 / 230 );
			});
			
			
		</script>
		<style>
			.explainModel .explainContent{background: #fff;
				position: fixed;
				bottom: 0px;
				z-index: 10000;
				padding: 0px 10px;
				-webkit-transition: all .3s;
				transition: all .3s;
				-webkit-transform: translateY(100%);
				transform: translateY(100%);
			}
			.explainModel .explainContent.active{
				transform: translateY(0);
				-webkit-transform: translateY(0);
			}
			.explainModel .explainContent div{
				padding-bottom: 10px;
				max-height: 175px;
				overflow-y: scroll;
				color: #999;
			}
			.explainModel .explainBtn{
				text-align: center;
				height: 45px;
				line-height: 45px;
				background: red;
				color: #fff;
				color: #fff;
				display: block;
				border-radius: 5px;
			}
			.explainModel p{
				text-align: center;
				padding: 15px 0px;
				font-size: 18px;
			}
			.coupon_mask {
				position: fixed;
				top: 0px;
				left: 0px;
				background: rgba(0,0,0,0.4);
				z-index: 888;
				width: 100%;
				height: 100%;

			}
			.video{
				display:none;
			}
			.bg_style{
				height: 921px;
				background-color: #000;
				position: fixed;
				z-index: 500;
				left: 0;
				top: 0;
				width: 100%;
				height: 100%;
				opacity: 0.3;
			}
			
			.store_live{
				background: url('./static/images/video_backgroud.png')  no-repeat;
				width: 100%;
				height: 200px;
				z-index: 999;
				margin: 0 auto;
				top: 34%;
				-webkit-background-size:cover;
				-moz-background-size:cover;
				-o-background-size:cover;
				background-size:cover;
				position: fixed;
				background-size:100% 100%;
			}
			.explain{line-height:14px;}
			.explain .weights{
				margin:0px 5px;
			}
			.explain .weight{

				padding: 3px 5px;
				margin-left: 0px !important;
			}
			.explain .instruction{
				color: red;
				border: 1px solid red;
				padding: 3px 5px;
				margin-left: 10px !important;
			}
			.explain .instruction a{
				color: red;

			}
		</style>
		{pigcms{$shareScript}
		{pigcms{$coupon_html}
	</body>
</html>

