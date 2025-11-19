<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta http-equiv="Expires" content="-1">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Pragma" content="no-cache">
<meta name="format-detection" content="telephone=no"/>
<meta charset="utf-8">
<title>商城首页</title>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/mall.css"/>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/swiper.min.css"/>
<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
<script type="text/javascript">var noAnimate = true;</script>
<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/swiper.min.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
<script>var cat_fid = "{pigcms{$cat_fid}", ajax_url = '{pigcms{:U("LocalShopMall/ajax_list")}', ajax_url_root = '{pigcms{:U("LocalShopMall/ajax_index")}';
var open_extra_price =Number("{pigcms{$config.open_extra_price}");
var extra_price_name ="{pigcms{$config.extra_price_alias_name}";</script>
<!--[if lte IE 9]>
<script src="{pigcms{$static_path}js/html5shiv.min.js"></script>
<![endif]-->
<script type="text/javascript" src="{pigcms{$static_path}js/shopMall.js?t=0005" charset="utf-8"></script>
	<style>
		.menu li {
			width: 20% !important;
		}
		.recommend{
			border-top: 1px solid #edebeb;
			border-bottom: 1px solid #edebeb;
			background-color: white;
			margin-bottom: 10px;
			height: auto;
		}
		.recommendBox{
			border-bottom: 1px solid #f1f1f1;
			overflow: hidden;
		}
		.recommendLeft{
			width: 50%;
			border-right: 1px solid #f1f1f1;
			float: left;
			margin-left: -1px;
			height: 180px;
		}
		.img_max{width:100%;height:100%}
		.recommendRight{
			float: left;
			width: 50%;
		}
		.recommendRightTop{
			border-bottom: 1px solid #f1f1f1;
		}
	</style>
</head>
<body>
	<header class="hasManyCity" style="z-index:111;display:none">
		<div id="searchBox">
			<a href="{pigcms{:U('LocalShopMall/search')}">
				<i class="icon-search"></i>
				<span>请输入商品名称</span>
			</a>
		</div>
	</header>
	<section class="homepage" id="listBanner">
		<div class="swiper-container swiper-container1" >
			<div class="swiper-wrapper"></div>
			<div class="swiper-pagination"></div>
		</div>
		<a href="{pigcms{:U('LocalShopMall/search')}" class="Cable">
			<div><i></i>请输入商品名称</div>
		</a>
	</section>
	<section id="listImg" class="homepages" >
        <volist name="localization_mall_store_adver" id="vo">
            <div class="link-url" data-url="{pigcms{$vo.url}">
                <img src="{pigcms{$vo.pic}" style="width:100%"/>
            </div>
        </volist>
	</section>

    <section class="menu" id="listSlider">
        <div class="swiper-container swiper-container5" >
            <div class="swiper-wrapper"></div>
            <div class="swiper-pagination swiper-pagination5"></div>
        </div>
    </section>

    <section class="recommend"  <if condition='!$localization_mall_center_adver'>style="display:none;"<else/>style="height:auto;"</if>>
		<div class="recommendBox">
			<div class="recommendLeft link-url" data-url="{pigcms{$localization_mall_center_adver.0.url}">
				<img src="{pigcms{$localization_mall_center_adver.0.pic}" alt="{pigcms{$localization_mall_center_adver.0.name}"/>
			</div>
			<div class="recommendRight">
				<div class="recommendRightTop link-url" data-url="{pigcms{$localization_mall_center_adver.1.url}">
					<img src="{pigcms{$localization_mall_center_adver.1.pic}" alt="{pigcms{$localization_mall_center_adver.1.name}"/>
				</div>
				<div class="recommendRightBottom link-url" data-url="{pigcms{$localization_mall_center_adver.2.url}">
					<img src="{pigcms{$localization_mall_center_adver.2.pic}" alt="{pigcms{$localization_mall_center_adver.2.name}"/>
				</div>
			</div>
		</div>
	</section>
	<section class="slideBox" id="slideBox">
		<div class="swiper-container hd" id="swiper-container2" >
			<div class="swiper-wrapper" ></div>
		</div>
		<div class="he45"></div>
		<div class="swiper-container bd"  id="swiper-container3" >
			<div class="swiper-wrapper"></div>
		</div>
	</section>
	<script type="text/javascript">
	//分类定位 
	$(window).scroll(function() {
		if ($(window).scrollTop() > $(".slideBox").offset().top) {
			$(".slideBox .hd").addClass("nav_topfied");
			$(".he45").css("display","block");
		}else{
			$(".slideBox .hd").removeClass("nav_topfied");
			$(".he45").css("display","none");
		}
	});
	</script>
	<!-- 底部 -->
	<include file="footer"/>
</body>
<script id="goodsListBoxTpl" type="text/html">
{{# for(var i = 0, len = d.goods_list.length; i < len; i++){ }}
	<a href="{{ d.goods_list[i].url }}">

		<div class="bd_img">
			<img src="{{ d.goods_list[i].image }}" width="100%"/>
            {{# if(d.goods_list[i].jd_sku_id > 0){ }}
                <div style="position: absolute;top: 0px;line-height: 20px;font-size: 12px;background: #fe5842;text-align: center;border-radius: 0px 0px 5px 5px;padding: 0 5px;color: #fff;">京东配送</div>
            {{# } }}
		</div>
		<div class="bd_text">
			<h2 style="font-size:14px;">{{ d.goods_list[i].name }}</h2>
			<div class="Price clr">
				<div class="fl">
					<span>{pigcms{$config['Currency_symbol']}<i style="font-size:18px;">{{ d.goods_list[i].price }}{{# if(open_extra_price==1&&d.goods_list[i].extra_pay_price>0){ }}+{{ d.goods_list[i].extra_pay_price }}{{ extra_price_name }}{{# } }}</i></span>
					{{# if (d.goods_list[i].is_seckill_price){}}
					<del>原价{{ d.goods_list[i].old_price }}</del>
					{{# } }}
				</div>
				{{# if (d.goods_list[i].sell_count > 0){}}
				<div class="fr">已售{{ d.goods_list[i].sell_count }}单</div>
				{{# } else if(d.goods_list[i].is_new == 1) { }}
				<div class="fr">新品上架</div>
				{{# } }}
			</div>
		</div>
		{{# if (d.goods_list[i].is_seckill_price){}}
        {{# if (d.goods_list[i].max_num > 0) { }}
		<div class="discount">限时优惠{{d.goods_list[i].max_num}}{{ d.goods_list[i].unit }}</div>
        {{# } else { }}
		<div class="discount">限时优惠</div>
        {{# } }}
		{{# } else if (d.goods_list[i].max_num > 0) { }}
        <div class="discount">限购{{d.goods_list[i].max_num}}{{ d.goods_list[i].unit }}</div>
        {{# } }}
	</a>
{{# } }}
</script>

<script id="listCategoryListTpl" type="text/html">
{{# for(var i in d){ }}
{{# if (i == 0){ }}
	<div class="swiper-slide active-nav" data-id="{{ d[i].id }}">
{{# }else{ }}
	<div class="swiper-slide" data-id="{{ d[i].id }}">
{{# } }}
	<!-- <i class="jx"></i>  -->
	<span data-id="{{ d[i].id }}">{{ d[i].name }}</span>
	</div>
{{# } }}
</script>

<script id="listGoodsContentListTpl" type="text/html">
{{# for(var i in d){ }}
	<div class="swiper-slide"><div class="bd_a clr"></div></div>
{{# } }}
</script>

<script id="listBannerSwiperTpl" type="text/html">
	{{# for(var i = 0, len = d.length; i < len; i++){ }}
		<div class="swiper-slide">
			<a href="{{ d[i].url }}">
				<img src="{{ d[i].pic }}" alt="{{ d[i].name }}" width="100%"/>
			</a>
		</div>
	{{# } }}
</script>
<script id="listSliderSwiperTpl" type="text/html">
	{{# for(var i = 0, len = d.length; i < len; i++){ }}
		{{# if(i%10 == 0){ }}
			<div class="swiper-slide">
				<ul class="icon-list">
		{{# } }}
					<li class="icon">
						<a href="{{ d[i].url }}">
							<span class="icon-circle">
								<img src="{{ d[i].pic }}" alt="{{ d[i].name }}" width="40" height="40"/>
							</span>
							<span class="icon-desc">{{ d[i].name }}</span>
						</a>
					</li>
		{{# if(i != 0 && ((i+1)%10 == 0 || i+1 == len)){ }}
				</ul>
			</div>
		{{# } }}
	{{# } }}
</script>
<script type="text/javascript">
window.shareData = {
			"moduleName":"Mall",
			"moduleID":"0",
			"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
			"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Mall/index')}",
			"tTitle": "商城首页-列表",
			"tContent": "{pigcms{$config.site_name}"
};
</script>
{pigcms{$shareScript}
{pigcms{$coupon_html}
<if condition="$is_app_browser">
<script type="text/javascript">
    
    if("{pigcms{$app_browser_type}" == 'android'){
		window.lifepasslogin.shareLifePass("商城首页-列表", "{pigcms{$config.site_name}", "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>", "{pigcms{$config.site_url}{pigcms{:U('Mall/index')}");
	} else {
		iosCommon.iosFunction('shareLifePass', {name:'shareLifePass', imgUrl: window.shareData.imgUrl, sendFriendLink:window.shareData.sendFriendLink, tTitle:window.shareData.tTitle, tContent:window.shareData.tContent });
	}
</script>
</if>
</html>