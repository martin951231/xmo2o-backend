<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>首页</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<!-- swiper -->
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css" />
		<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/swiper.min.js" charset="utf-8"></script>
		
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js" charset="utf-8"></script>
		<script type="text/javascript">
			var location_mode={pigcms{$config.location_mode|intval=###};
			var user_long = '0',user_lat  = '0';
			var currency_symbol = "{pigcms{$config.Currency_symbol}";
		</script>
        <style>
            body{
                background: #F4F4F4;
            }
            *{margin: 0;padding: 0;}
            .hidden{display: none !important;}

            /*iconfont*/
            @font-face {
			  font-family: 'iconfont';  /* project id 792293 */
			  src: url('//at.alicdn.com/t/font_792293_irmehlvoa5o.eot');
			  src: url('//at.alicdn.com/t/font_792293_irmehlvoa5o.eot?#iefix') format('embedded-opentype'),
			  url('//at.alicdn.com/t/font_792293_irmehlvoa5o.woff2') format('woff2'),
			  url('//at.alicdn.com/t/font_792293_irmehlvoa5o.woff') format('woff'),
			  url('//at.alicdn.com/t/font_792293_irmehlvoa5o.ttf') format('truetype'),
			  url('//at.alicdn.com/t/font_792293_irmehlvoa5o.svg#iconfont') format('svg');
			}

			.iconfont{
			    font-family:"iconfont" !important;
			    font-size:16px;font-style:normal;
			    -webkit-font-smoothing: antialiased;
			    -webkit-text-stroke-width: 0.2px;
			    -moz-osx-font-smoothing: grayscale;
			}

			/*1px边框*/
			.border-b::after{content: '';position: absolute;bottom: 0;left: 0;height: 1px;right: 0;background: #e5e5e5;transform: scaleY(0.5);}
			.mb5{margin-bottom: 5px;}
			.mt5{margin-top: 5px;}
			.mr5{margin-right: 5px;}
			.mb2{margin-bottom: 2px;}

			/*不换行*/
			.nowrap{white-space: nowrap;}

            /**flex**/
			.rel{position: relative;}
			.flex{display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; display: box;display:flex;}
			.flex_justify{-webkit-box-pack: justify;-webkit-justify-content: space-between;-ms-flex-pack: justify;box-pack: center;justify-content: space-between;}
			.flex-justify-start{-webkit-box-pack: start;-webkit-justify-content: flex-start;-ms-flex-pack: start;box-pack: start;justify-content: flex-start;}
			.flex-align{-webkit-box-align: center;-moz-align-items: center;-webkit-align-items: center; box-align: center;align-items: center;}
			.flex_one{-webkit-box-flex: 1;-webkit-flex: 1;flex: 1;}
			.flex-wrap{-webkit-flex-wrap: wrap;-moz-flex-wrap: wrap;-ms-flex-wrap: wrap;-o-flex-wrap: wrap;flex-wrap:wrap;}
			.flex-grow{-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex-grow: 1;-ms-flex: 1;flex-grow: 1;}

			/*swiper分页器样式*/
			.swiper-container .swiper-pagination-bullet{width: 6px;height: 6px;background: #06C1AE;}
			.swiper-container .swiper-pagination-bullet-active {width: 14px;border-radius: 3px;}
			.swiper-container.swiper-container-horizontal>.swiper-pagination-bullets .swiper-pagination-bullet{margin:0 3px;}
			.swiper-container.swiper-container-horizontal>.swiper-pagination-bullets, .swiper-pagination-custom, .swiper-pagination-fraction{bottom: 5px;left: 50%;transform: -webkit-translateX(-50%);transform: translateX(-50%);width: auto;}

			.content-wrapper{background: #fff;}
			@media only screen and (-webkit-device-pixel-ratio: 3) and (device-height: 812px) and (device-width: 375px) {
				.content-wrapper{background: #fff;padding-bottom: 34px;}
			}
			
			.search-wrapper{position: fixed;top: 0;left: 0;right: 0;z-index: 10;}
			.search-wrapper.default{background: #06C1AE;}
			.search-wrapper.pure{background: -webkit-linear-gradient(to bottom, rgba(0,0,0, .3), rgba(0,0,0, 0));background: linear-gradient(to bottom, rgba(0,0,0, .3), rgba(0,0,0, 0));}
			.search-wrapper .status-bar{height: 20px;padding: 0 10px;box-sizing: border-box;text-align: center;}
			.search-wrapper .status-bar img{width: 100%;margin-top: 2px;}
			.search-bar{height: 44px;color: #fff;padding: 0 10px;}
			.search-bar .address{font-size: 14px;margin-right: 15px;}
			.search-bar .address.ellipsis{max-width: 80px;overflow: hidden;}
			.search-bar .address img{height: 8px;margin-left: 5px;}
			.search-bar .search{height: 30px;line-height: 30px;background: rgba(255, 255, 255, .4);padding: 0 45px 0 10px;border-radius: 15px;border: 1px solid rgba(255, 255, 255, .4)}
			.search-bar .search img{height: 12px;margin-right: 5px;}
			.search-bar>.icon{position: relative;}
			.search-bar .sao{height: 19px;padding:10px 8px 10px 16px; }
			.search-bar .more{height: 22px;margin-right: -10px;padding: 10px 10px 10px 8px;}
			.search-bar .wx{height: 32px;}

			.more-mask{position: absolute;right: 9px;background: #fff;border-radius: 5px;box-shadow: 1px 1px 5px #aaa;}
			.more-mask .item img{width: 24px;height: auto;margin-right: 5px;vertical-align: middle;}
			.more-mask .item{height: 40px;width: 135px;font-size: 14px;padding: 0 10px;display: flex;align-items: center;box-sizing: border-box;}
			.more-mask .item.border-b::after{left: 32px;right: 10px;}
			.more-mask .item:last-child::after{height: 0;}
			.more-mask .icon-arr-top{position: absolute;right: -3px;top: -23px;font-size: 28px;color: #fff;}


			.pure-header{height: 198px;overflow: hidden;background: #06c1ae;}
			.pure-header .swiper-container{height: 100%; object-fit: cover;}
			.pure-header img{width: 100%;height: 100%;}
			.get-point{position: absolute;bottom: 10px;left: 10px;color: #fff;font-size: 12px;background: rgba(0,0,0,.5);border-radius: 20px;z-index: 999;}
			.get-point.active{animation: myfirst .6s linear;}
			.get-point img{width: 20px;height: 20px;border-radius: 50%;margin: 5px 5px 5px 10px;}
			.get-point span{padding-right: 10px;}
			.get-point.fixed{position: fixed;top: 54px;}

			@-webkit-keyframes myfirst {
			  0%   {transform: -webkit-translateX(-100%);transform: translateX(-100%);}
			  100% {transform: -webkit-translateX(0);transform: translateX(0);}
			}
			@keyframes myfirst {
			  0%   {transform: -webkit-translateX(-100%);transform: translateX(-100%);}
			  100% {transform: -webkit-translateX(0);transform: translateX(0);}
			}

			.default-header .btn-nav-wrapper{min-height: 44px}
			.default-header .btn-nav{ background: #06C1AE;}
			.default-header .btn-nav .item{width: 25%;padding: 59px 0 18px;color: #fff;font-size: 14px;text-align: center;}
			.default-header .btn-nav .item img{width: 35px;height: 35px;margin-bottom: 5px;}
			.slider-small.swiper-container{margin: 10px 10px 0;height: 77px;border-radius: 5px;}
			.slider-small img{height: 100%;width: 100%;}

			.slider-box{padding-bottom: 25px;}
			.slider-box .item{font-size: 13px;text-align: center;width: 20%;padding-top: 15px;position: relative;}
			.slider-box .item img{height: 32px;min-width: 32px;}
			.slider-box .item .tip{position: absolute;top: 0px;left: 40%;background: red;color: #fff;border-radius: 10px 10px 10px 0;font-size: 10px;padding: 1px 5px;border: 2px solid #fff;white-space: nowrap;}
			.slider-box .swiper-slide .item:nth-child(5n+5) .tip{left: 20%}

			.new-list, .get_coupon{padding:0 10px 10px;font-size: 14px;white-space: nowrap;width: 100%;box-sizing: border-box;overflow: hidden;}
			.new-list .icon{width: 25px;}
			.new-list .list-box-wrapper{width: -webkit-calc(100% - 65px);width: calc(100% - 65px);overflow: hidden;}
			.new-list .list-box{height: 20px;width: -webkit-calc(100% - 15px);width: calc(100% - 15px);}
			.new-list .list-box .label{padding-right: 5px;color: red;white-space: nowrap;}
			.new-list .list-box .txt{overflow: hidden;white-space: nowrap;text-overflow: ellipsis;}

			.get_coupon .news-swiper{padding-left: 10px;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;}
			.get_coupon .icon{height: 18px;}
			.get_coupon .btn{margin-left: 6px;background: linear-gradient(#f76b1b, #fe1a3e);color: #fff;border-radius: 11px;padding: 3px 8px;font-size: 12px;font-weight: 500;letter-spacing: 2px;}

			.gap{width: 100%;background: #f4f4f4;height: 10px;}

			.four-img-advert{margin: 20px 10px 0;position: relative;}
			.four-img-advert img{width: 23%;height: 55px;}

			.switch-btn{position: fixed;right: 0;bottom: 200px;width: 20px;padding: 20px 0 20px 10px;transition: all .5s;z-index: 99999;}
			.switch-btn.active{right: -20px;}
			.switch-btn .btn{width:20px;font-size:12px;color:#fff;text-align:center;border-radius:4px 0 0 4px;padding:5px 0;line-height:1.3;background:-webkit-linear-gradient(#06b3a1, #09f1b7);background:linear-gradient(#06b3a1, #09f1b7);}
			.switch-btn .btn img{width: 12px;height: 12px;margin-top: 2px;}

			
			.slider-center.swiper-container{margin: 20px 10px 0;height: 93px;border-radius: 5px;}
			.slider-center img{height: 100%;width: 100%;}

			.title-box{margin: 10px 10px -5px;position: relative;font-size: 12px;}
			.title-box.shop-list-title{margin:15px 10px 0;}
			.title-box .title{font-size: 18px;font-weight: 500;margin-right: 5px;vertical-align: top}
			.title-box .desc{color:#bbb;position: relative;bottom: -7px;}
			.title-box .time{font-size: 12px;width: 18px;height: 18px;background: #333;color: #fff;display: inline-block;
			text-align: center;line-height: 17px;margin: 2px 4px 0;border-radius: 2px;}
			.title-box .more{font-size: 12px;color: #999;white-space: nowrap;}
			.title-box .more .iconfont{font-size: 24px;vertical-align: middle;margin-left: -5px;margin-right: -8px;}

			.goods-box{margin: 0 10px;}
			.goods-item{width: calc(33.333% - 10px);margin-top: 10px;margin-right: 10px;overflow: hidden;position: relative;}
			.goods-item:last-child, .brand-box .goods-item:nth-child(2){margin-right: 0;}
			.goods-item .img-box{position: relative;padding-top: 100%;overflow: hidden;}
			.goods-item .img-box .icon{position: absolute;bottom: 0;left: 0;background: -webkit-linear-gradient(to right, #ffb269 , #ff4437);background: linear-gradient(to right, #ffb269 , #ff4437);color: #fff;border-radius: 0 10px 0 0;font-size: 11px;padding: 1px 5px;}
			.goods-item img{width: 100%;height: 100%;border-radius: 2px;display: block;position: absolute;top: 0;left: 0;right: 0;bottom: 0;object-fit: cover;}
			.goods-item .img-box.just-img{height: 176.73px;padding-top: 0;}
			.goods-item .img-box.just-img img{width: 100%;}
			.goods-item .name, .goods-item .desc,.goods-item .price-box{font-size: 14px;font-weight: 500;white-space: nowrap;text-overflow: ellipsis;width: 100%;overflow: hidden;margin-top: 2px;line-height: 1.4;}
			.goods-item .desc{font-size: 12px;font-weight: 400;}
			.goods-item .price-box{color:#999;font-size: 12px;}
			.goods-item .price-box .price{color:red;font-size: 14px;}
			.goods-item .old-price{color: #999;margin-left: 5px;text-decoration: line-through;font-size: 12px;}

			.brand-box{margin-left:10px;}
			.brand-img{width: calc(66.66% - 10px);height: 176.73px;margin-top: 10px;margin-right: 10px;overflow: hidden;position: relative;}
			.brand-img img{width: 100%;height: 100%;display: block;border-radius: 2px;position: absolute;top: 0;left: 0;right: 0;bottom: 0;}

			.shop-item{padding: 10px;display: flex;position: relative;width: 100%;box-sizing: border-box;}
			.shop-item.border-b::after{left: 10px;}
			.shop-item.border-b:last-child::after{height: 0;}
			.shop-item .shop-img{width: 102px;height: 72px;border-radius: 5px;margin-right: 10px;overflow: hidden;position: relative;}
			.shop-item .shop-img img{width: 100%;height: 100%;object-fit: cover;}
			.shop-item .info-box{position: relative;width: -webkit-calc(100% - 112px);width: calc(100% - 112px);min-height: 80px;}
			.shop-item .small{color: #333;font-size: 12px;}
			.shop-item .small.bg{background: #f4f4f4;margin-right: 5px;margin-bottom: 5px;padding: 0 10px;}
			.shop-item .name-box{position: relative;}
			.shop-item .name-box.food .icon{margin-left: 5px;font-size: 12px;border-radius: 3px;align-self: center;color: #fff;height: 20px;line-height: 20px;padding: 0 5px;background:-webkit-linear-gradient(to right, #facd7a , #faa300);background:linear-gradient(to right, #facd7a , #faa300);}
			.shop-item .name{font-size: 16px;font-weight: 500;margin-top: -4px;}
			.shop-item .gray{color:#bbb;}
			.shop-item .red{color:#f6503e;} 
			.shop-item .orange{color: #f17019;font-weight: bold;}
			.shop-item .green{color: #06c1ae;border: 1px solid #06c1ae;margin-left: 5px;font-size: 11px;padding: 1px 3px;border-radius: 2px;}
			.shop-item .bold{font-weight: bold;}
			.shop-item .right{position: absolute;right: 0;}
			.shop-item .bottom{position: absolute;bottom: 0;}
			.shop-item .desc{margin-bottom:3px;width: 100%;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;}
			.shop-item .price-box{line-height: 1;align-items: baseline;}
			.shop-item .now-price{margin-right: 5px;font-size: 18px;font-weight: 500;}
			.shop-item .discount-icon{display: flex;flex-wrap: wrap;}
			.shop-item .discount-icon .icon{margin-right:5px;margin-bottom: 5px;font-size:12px;padding:0 3px;border-radius:2px;color:#ce847f;border:1px solid #f4cfcc;background:#fff4f2; }
			.shop-item .store-tag{height: 20px;white-space:nowrap;}
			.shop-item .store-tag span{display: inline-block;width: 20px;height: 100%;background: pink;font-size: 14px;vertical-align: top;color: #fff;margin-left: 5px;text-align: center;line-height: 20px;}
			.shop-item .store-tag span.group-tip{background: #74da1c;}
			.shop-item .store-tag span.food-tip{background: #fc5e03;}
			.shop-item .store-tag span.appoint-tip{background: #f8c717;}
			.shop-item .img-tip{position: absolute;left: 0;bottom: 0;color: #fff;padding: 0 5px;font-size: 12px;border-radius: 0 10px 0 5px;
			background: -webkit-linear-gradient(to right, rgb(247, 105, 57) , rgb(255, 60, 0));background: linear-gradient(to right, rgb(247, 105, 57) , rgb(255, 60, 0));}
			.shop-item .icon-star{color: #ffc900;font-size:12px;margin-right:5px;}
			.shop-item .icon-line{line-height: 16px;font-size: 14px;}
			.shop-item .name-box .food-icon{background:-webkit-linear-gradient(to right, rgb(243, 137, 101) , rgb(255, 60, 0));background:linear-gradient(to right, rgb(243, 137, 101) , rgb(255, 60, 0));color: #fff;
			font-size: 12px;height: 20px;line-height: 20px;padding: 0 5px;margin-left: 5px;border-radius: 3px;display: inline-block;}
			.shop-item .score{margin-right: 10px;font-size: 12px;}
			.shop-item .sale-tip{background: -webkit-linear-gradient(to right, rgb(247, 105, 57) , rgb(255, 60, 0));background: linear-gradient(to right, rgb(247, 105, 57) , rgb(255, 60, 0));color: #fff;font-size: 12px;margin-right: 5px;padding: 2px 5px;border-radius: 2px;}
			.shop-item .news{margin: 0;font-size: 12px;}
			.shop-item .news .slider-goods{height: 20px;}
			.news-mask{position: absolute;top: 0;left: 0;right: 0;bottom: 0;z-index: 10;}
			/* 发票 */
			.invoice{color: #06c1ae; border: 1px solid #c5f3ef;}
			/* 平台首单优惠 平台优惠 */
			.system_newuser, .system_minus{color: #aa8023; border: 1px solid #f3e5c6;}  
			/* 打折 部分商品限时优惠  部分商品分类折扣优惠 配送费优惠 店铺首单优惠 店铺优惠 */
			.discount, .isdiscountgoods, .isdiscountsort, .delivery, .newuser, .minus{color: #ff3e4a;border: 1px solid #fce5e7;}
			.discount-icon>span{margin-right: 5px;margin-bottom: 5px;font-size: 11px;padding: 0 3px;border-radius: 2px;}
          	
          	.bottom-txt{font-size: 12px;color: #999;line-height: 40px;height: 40px;text-align: center;}

          	.icon-top{display: block;height: 25px;border-bottom: 0px;text-align: center;overflow: hidden;}
          	.icon-top .icon{ font-size: 22px;position: relative;top: 4px;color: #06C1AE;}


          	/* 优惠券 */
			.coupon-mask{position: fixed;top: 0;left: 0;right: 0;bottom: 0;background: rgba(0,0,0,0.7);height: 0;z-index: 999999;display: none;height: 100%;}
			.coupon-mask.active{height: 100%;}
			.mask-content{background: #fff;font-size: 14px;transform: translateY(-100%);transition: all .5s;}
			.mask-content.active{transform: translateY(0);}
			.coupon-box{position: absolute;top: 50%;left: 50%;transform: translate(-50%, -50%);z-index: 4;width: 80%;}
			.coupon-box .top-img{width: 100%;display: block;height: auto;}
			.coupon-box .detail{width: 100%;background: #fc3e2d;box-sizing: border-box;padding-bottom: 15px;border-radius: 0 0 5px 5px;}
			.coupon-box .detail img{width: 100%;display: block;height: auto;}
			.coupon-box .detail .list{max-height: 200px;overflow: auto;}
			.coupon-box .detail .list .item:last-child{margin-bottom: 0;}
			.coupon-box .detail .item{margin:0 auto 10px ;position: relative;width: 90%;}
			.coupon-box .detail .item .desc{position: absolute;top: 0;left: 0;bottom: 0;width: 100%;box-sizing: border-box;font-size: 16px;color: #333;}
			.coupon-box .detail .item .desc .num{width:33.6%;overflow: hidden;padding: 0 10px;box-sizing: border-box;}
			.coupon-box .detail .item .desc .limit{width:66.4%;overflow: hidden;padding: 0 10px;box-sizing: border-box;}
			.coupon-box .detail .item .desc .limit .txt{text-overflow: ellipsis;white-space: nowrap;overflow: hidden;box-sizing: border-box;padding:0 5px;}
			.coupon-box .detail .item .desc .font12{font-size: 12px;color: #666;}
			.coupon-box .detail .item .desc .font16{font-size: 18px;}
			.coupon-box .detail .item .desc .red{color: #ff3e4a}
			.coupon-box .detail .item .desc .num .red{font-weight: bold;}
			.coupon-box .detail button{font-size: 16px;color: #410509;background: #ffda31;border-radius:30px;width: 90%;margin: 10px 5% 0;line-height: 2.5;outline: none;border: none;}
			button::after{border: none;}
			.coupon-box .close-icon{text-align: center;color: #fff;margin-top: 10px;}
			.coupon-box .close-icon .icon{width: 35px;}

		</style>
	</head>
	<body>
		<div class='content-wrapper'>
			<div class="header-wrapper rel" style="min-height: 44px;">
				<div class="search-wrapper">
					<div class="flex flex-align flex_justify search-bar">
						<div class="address link <if condition="$config.open_multilingual neq 1">ellipsis</if>" data-url="{pigcms{:U('Changecity/index',array('index_new'=>'1'))}"><span><if condition="$now_select_area">{pigcms{$now_select_area['area_name']}<else/>{pigcms{$now_select_city['area_name']}</if></span><img src="../tpl/System/Static/images/arrow-bottom.png"></div>
						<div class="flex-grow search link" data-url="/wap.php?g=Wap&amp;c=Search&amp;a=index"><img src="../tpl/System/Static/images/sousuo.png"><span>搜一搜</span></div>
						<img class="icon sao qrcodeBtn" src="../tpl/System/Static/images/saoma.png">
						<img class="icon more" src="../tpl/System/Static/images/more.png">
					</div>
					<view class='more-mask hidden'>
						<text class='icon iconfont icon-arr-top'>&#xe61b;</text>
				    </view>
				</div>

				<div class="pure-header hidden" style="background:{pigcms{$config['mobile_head_color']}">
					<div class="swiper-container" >
						<div class="swiper-wrapper"></div>
				        <!-- Add Pagination -->
				        <div class="swiper-pagination"></div>
					</div>
					<div class='flex flex-align get-point active'></div>
				</div>
				

				<div class="default-header hidden" >
					<div class="btn-nav-wrapper">
						<div class="flex flex-align btn-nav" style='background-color: {pigcms{$config["mobile_head_color"]};'></div>
					</div>
					
					<div class="swiper-container slider-small">
						<div class="swiper-wrapper"></div>
				        <div class="swiper-pagination"></div>
					</div>
				</div>
			</div>

			<div class="swiper-container slider-box">
				<div class="swiper-wrapper">
		        </div>
		        <!-- Add Pagination -->
		        <div class="swiper-pagination"></div>
			</div>

			<div class="new-list flex flex-align link" data-url="/wap.php?g=Wap&c=Systemnews&a=index">
				<img class="icon" src="{pigcms{$static_path}img/plat-news.png">
				<div class="rel list-box-wrapper">
					<div class="swiper-container list-box">
						<div class="swiper-wrapper"></div>
					</div>
					<div class='news-mask'></div>
				</div>
				<span>| 更多</span>
			</div>

			<!-- 优惠券立即领取 -->
		    <div class='get_coupon flex flex-align link hidden'>
		      <img class="icon" src='{{homeData.entrance.img}}' />
		      <div class='news-swiper flex-grow'>新人领券</div>
		      <span class='btn'>立即领取</span>
		    </div>

			<div class="gap"></div>

			<div class="four-img-advert flex flex_justify"></div>

			<div class="swiper-container slider-center">
				<div class="swiper-wrapper"></div>
		        <!-- Add Pagination -->
		        <div class="swiper-pagination"></div>
			</div>

			<div class="buy-discount-info">
				<div class='flex flex_justify flex-align title-box'>
					<div class="flex flex-align">
						<span class='title'></span>
						<span class="time-box"></span>
					</div>
					<div class='more link' data-url='/wap.php?g=Wap&c=Home&a=index_buy'>更多<span class='icon iconfont'>&#xe601;</span></div>
				</div> 
				<div class='flex flex-wrap goods-box'></div>
			</div>
			
			<div class="five-box"></div>
			

			<div class='flex flex_justify flex-align title-box shop-list-title hidden'>
				<div>
					<span class='title'>猜你喜欢</span>
					<span class='desc'>推荐更多好物</span>
				</div>
			</div> 
			<div class="list-wrapper"></div>
			<div class="bottom-txt">
				<span class="loading hidden">加载更多数据</span>
				<span class="complete hidden">没有更多数据了</span>
			</div>
			
			<div class="switch-btn <if condition="$config['is_demo_domain'] neq 1">hidden</if>">
				<div class="default btn hidden">标准版<img src="../tpl/System/Static/images/arrow-left.png"></div>
				<div class="pure btn hidden">清新版<img src="../tpl/System/Static/images/arrow-right.png"></div>
			</div>

			<div class='mask coupon-mask'>
			    <div class='coupon-box'>
			      <img class='top-img' src='{pigcms{$static_path}img/sn_coupon11.png'>
			      <div class='detail' >
			      	<!-- 优惠券列表 -->
		      		<div class="list"></div>
			          
			        <button class='closeMask'>去下单</button>
			      </div>

			      <div class='close-icon'><img class="icon" src="{pigcms{$static_path}img/cha.png"></div>
			    </div>
			</div>
		</div>



		<include file="Public:footer"/>
		
    	<script type="text/javascript">

    		var is_load = true ; // 监听列表数据是否请求完毕，避免同时请求多次
    		var page = 1;
			var complete = false;
			var request_param = {
				'Device-Id': "{pigcms{$ticket_arr['Device-Id']}",
				ticket: "{pigcms{$ticket_arr['ticket']}",
				now_long: "{pigcms{$config.system_lang}",
				now_city: "{pigcms{$config.now_city}",
				app_version: 1300
			};
			var mobile_color = '{pigcms{$config["mobile_head_color"]}'; // 主颜色
			var is_show_delivery = "{pigcms{$config.shop_show_delivermoney}"; // 是否显示配送费
    		$(function(){

    			FastClick.attach(document.body);
				getHomeData();
				getCoupon();
				getUserLocation({errorAction:1, useHistory: false, okFunction:'getBaiduLocation',errorFunction:'getBaiduLocation'});

    			// 扫一扫按钮
    			$('.qrcodeBtn').click(function(){
					if(motify.checkWeixin()){
						motify.log('正在调用二维码功能');
						wx.scanQRCode({
							desc:'scanQRCode desc',
							needResult:0,
							scanType:["qrCode"],
							success:function (res){
								// alert(res);
							},
							error:function(res){
								motify.log('微信返回错误！请稍后重试。',5);
							},
							fail:function(res){
								motify.log('无法调用二维码功能');
							}
						});
					}else{
						motify.log('您不是微信访问，无法使用二维码功能');
					}
				});

    			// 头部点击更多
    			$(document).on('click', '.search-wrapper .icon.more', function(){
    				if ($('.search-wrapper .more-mask').attr('class').indexOf('hidden') != -1) {
    					$('.search-wrapper .more-mask').removeClass('hidden');
    				} else {
    					$('.search-wrapper .more-mask').addClass('hidden');
    				}
    			})

    			// 判断是否显示页面切换按钮
    			var str = 'hf.pigcms.com, www.group.com, o2otest.pigcms.com';
    			if (str.indexOf(document.domain) == -1) {
    				$('.switch-btn').addClass('hidden');
    			}
    			// 头部切换
    			$(document).on('click', '.switch-btn', function(){
    				$(this).find('.btn').each(function(index, item){
    					var class_str = $(item).attr('class');
    					var header_str = class_str.split(' ');
    					var header_class = '.' +header_str[0] + '-header';
    					if (class_str.indexOf('hidden') == -1) {
    						$(item).addClass('hidden');
    						$(header_class).addClass('hidden');
    					} else {
    						$(item).removeClass('hidden');
    						$(header_class).removeClass('hidden');
    						$('.search-wrapper').attr('class', 'search-wrapper '+header_str[0]);
    					}
    				})
    				$('.search-wrapper.default').css('background',mobile_color);
    				$('.search-wrapper.pure').css('background','');
    			})

    			// 页面链接跳转
    			$(document).on('click', '.link', function(){
    				console.log('2222222', $(this).data('url'))
    				if ( $(this).attr('class').indexOf('more') != -1 && $(this).data('url').indexOf('lng') == -1) {
    					$(this).data('url', $(this).data('url') + '&lng=' + user_long + '&lat=' + user_lat)
    				}
    				
    				if ($(this).data('goodsid')) {
    					buyDiscountReport($(this).data('goodsid'));
    				}
    				if ($(this).data('url') != "") {
    					location.href = $(this).data('url');
    				}
    				
    			})

    			// 监听页面滚动，头部颜色切换，上拉加载，回到顶部按钮
    			var index_url = $('.footerMenu ul li:first-child a').attr('href');
    			var return_top = '<div class="icon-top hidden"><i class="icon iconfont">&#xe669;</i></div>';
    			$('.footerMenu ul li:first-child a').attr('href', 'javascript:;');
    			$('.footerMenu ul li:first-child a em').after(return_top);
    			
    			var index_name = $('.footerMenu ul li:first-child p').html()
    			$(document).on('scroll', window, function(e){
    				var distance = $('.bottom-txt').offset().top-$(document).scrollTop() +$('.bottom-txt').height()+50 - $(window).height()
    				if (!complete && distance <= 40 && !is_load) {
    					is_load = true;
    					getList('', user_long, user_lat);
    				} 

    				var scrollTop = $(window).scrollTop();
    				if($('.search-wrapper').attr('class').indexOf('pure') != -1 ) {
    					if (scrollTop >= $('.header-wrapper').height() - 44) {
    						$('.search-wrapper').css('background', mobile_color);
    						$('.get-point').css({'position':'fixed', 'top':'64px', 'bottom':'auto'});
    					} else {
    						$('.search-wrapper').css('background', '');
    						$('.get-point').css({'position':'', 'top':'', 'bottom':''});
    					}
    				} 

    				if ( scrollTop >= 50 ) {
    					$('.footerMenu ul li:first-child a em').addClass('hidden');
    					$('.footerMenu ul li:first-child p').html('<span style="color:'+ mobile_color +'">回到顶部</span>');
    					$('.icon-top .icon').css('color', mobile_color)
    					$('.icon-top').removeClass('hidden');
    				} else {
    					$('.footerMenu ul li:first-child a em').removeClass('hidden');
    					$('.icon-top').addClass('hidden');
    					$('.footerMenu ul li:first-child p').html(index_name);
    				}

    				$('.switch-btn').addClass('active');

    				setTimeout(function(){
    					if ($(window).scrollTop() == scrollTop) {
    						$('.switch-btn').removeClass('active');
    					}
    				}, 500)
    				
    			})

    			$('.footerMenu ul li:first-child a').on('click', function(){
    				if ($('.footerMenu ul li:first-child a .icon-top').attr('class').indexOf('hidden') == -1) {
    					$('body,html').animate({ scrollTop: 0 }, 500);
    				}
    				
    			})


			    var swiper_goods = ''

    		})
			
			
			// 优惠券
			function getCoupon () {
				if (request_param.ticket) {
					$.post('/appapi.php?c=Home&a=rand_coupon', request_param, function(data){
						console.log('优惠券', data)
						if ( data.result && data.result.coupon.length > 0 ) {
							var html = ''
							for (var i=0; i<data.result.coupon.length; i++) {
								var item = data.result.coupon[i] ;
								html += "<div class='item'><img src='{pigcms{$static_path}img/sn_coupon2.png' id='ticketItem'><div class='desc flex flex-align'><div class='num'><div class='red'>"+ item.discount +"</div><div class='font12'>满"+ item.order_money +"减"+ item.discount +"</div></div><div class='limit'><div class='txt'>"+ item.name +"</div><div class='font12 red'>"+ item.days +"</div></div></div></div>"
							}
							$('.coupon-mask .list').append(html);
							$('.coupon-mask').show();
							if (data.result.coupon.length > 2) {
								$('.counpon-mask .list').css('max-height', $('.coupon-mask .item').height() * 2.5)
							}
							
						}
					})
				}
			}
			
			// 关闭优惠券弹窗 close-icon
			$(document).on('click', '.close-icon,.closeMask', function(){
				$('.coupon-mask').hide();
			})
			
			// 获取“猜你喜欢”店铺列表
			function getList () {
				if (page > 10) {
					complete = true;
					$('.bottom-txt .loading').addClass('hidden');
					$('.bottom-txt .complete').removeClass('hidden');
					return
				}
				var param = JSON.parse(JSON.stringify(request_param));
				param.page = page
				param.lat = user_lat
				param.lng = user_long
				$.post('/appapi.php?c=Home&a=plat_recommend', param, function(data){
						is_load = false
						if (data.result.list.length > 0) {
							for (var i=0; i<data.result.list.length; i++) {
								var item = data.result.list[i];
								var item_html = '';
								item.range = item.range ? item.range : ''
								item_html = '<div class="shop-item border-b link" data-url="'+ item.url +'"><div class="shop-img"><img src="'+ item.pic +'">';
								if (item.is_brand == 1) {
									item_html += '<span class="img-tip">品牌甄选</span>';
								}
								if (item.re_type == 'store') {
									item_html += '</div><div class="info-box"><div class="flex flex_justify name-box"><span class="name">'+ item.store_name +'</span>';
									if (item.business_label != "") {
										item_html += '<div class="store-tag">';
										if (item.business_label.indexOf("团") != -1) {
											item_html += '<span class="group-tip">团</span>';
										}
										if (item.business_label.indexOf("餐") != -1) {
											item_html += '<span class="food-tip">餐</span>';
										}
										if (item.business_label.indexOf("外") != -1) {
											item_html += '<span class="appoint-tip">外</span>';
										}
										item_html += '</div>'

									}
									item_html += '</div><div class="small desc"><span class="icon iconfont icon-star">&#xe70b;</span><span class="score">'+ item.star +'</span></div><div class="small desc orange bottom">'+ item.tip +'</div></div></div>'
								}
								if (item.re_type == 'shop') {
									item_html += '</div><div class="info-box"><div class="flex flex_justify name-box food"><span class="name">'+ item.store_name +'</span><span class="small nowrap">'+ item.range +'</span></div><div class="small desc"><span class="icon iconfont icon-star">&#xe70b;</span><span class="score">'+ item.star +'</span>月售'+ item.sale_count +'</div><div class="rel flex flex-center mb5"><span class="small">起送价{pigcms{$config["Currency_symbol"]}'+ item.price_1 +'</span>';
									if (is_show_delivery == 1) {
										item_html += '<span class="icon iconfont icon-line">&#xe613;</span><span class="small">配送费{pigcms{$config["Currency_symbol"]}'+ item.price_2 +'</span>'
									}
									item_html += '</div>'
									if (item.info_arr != null && item.info_arr.length > 0) {
										item_html += '<div class="discount-icon">';
										for (var j=0; j<item.info_arr.length; j++) {
											item_html += '<span class="'+ item.info_arr[j].type +'">'+ item.info_arr[j].text +'</span>'
										}
										item_html += '</div>';
									}
									item_html += '</div></div>';
								}
								if (item.re_type == 'meal') {
									item_html += '</div><div class="info-box"><div class="flex flex_justify name-box"><div class="name">'+ item.store_name +'</div><span class="small nowrap">'+ item.range +'</span></div> <div class="small desc"><span class="icon iconfont icon-star">&#xe70b;</span><span class="score">'+ item.star +'</span></div>';
									if (item.business_label != "") {
										item_html += '<div class="rel flex flex-align flex-wrap">';
										var arr = item.business_label.split(',');
										for (var j=0; j<arr.length; j++) {
											item_html += '<span class="small bg">'+ arr[j] +'</span>';
										}
										item_html += '</div>';
									}
	        						item_html += '<div class="small desc orange">'+ item.tip +'</div></div></div>';
								}
								if (item.re_type == 'hotel') {
									item_html += '</div><div class="info-box"><div class="flex flex_justify name-box"><div class="name">'+ item.store_name +'</div><span class="small nowrap">'+ item.range +'</span></div> <div class="rel flex flex-align"><span class="icon iconfont icon-star">&#xe70b;</span><span class="score">'+ item.star +'</span><span class="small">'+ item.sale_count +'+人消费</span></div><div class="rel flex flex-align mb2"><span class="small">'+ item.address +'</span>';
									if (item.label != "") {
										var arr = item.label.split(',');
										item_html += '<div class="small right">';
										for (var j=0; j<arr.length; j++) {
											item_html += '<span class="green">'+ arr[i] +'</span>'
										}
										item_html += '</div>';
									}

	       							item_html += '</div><div class="rel flex price-box"><span class="small red bold">{pigcms{$config["Currency_symbol"]}</span><span class="red now-price">'+ item.price +'</span>';
	       							if (item.price_up == 1) {
	       								item_html += '<span class="small">起</span>';
	       							}
	       							item_html += '<span class="small right mt5 orange">'+ item.tip +'</span></div></div></div>';
								}
								if (item.re_type == 'group') {
									item_html += '</div><div class="info-box"><div class="flex flex_justify name-box"><span class="name"><span class="mr5">'+ item.store_name +'</span>'
									item.discount = (item.price / item.old_price).toFixed(2)
							        item.discount = item.discount * 100 
							        item.discount = ((item.discount > 0 && item.discount <= 50) || (item.price == 0 && item.old_price > 0)) ? true : false
							        if ((item.discount > 0 && item.discount <= 50) || (item.price == 0 && item.old_price > 0)) {
							        	item_html += '<span class="food-icon">超值</span>';
							        }	
							     
							        item_html += '</span><span class="mall nowrap">'+ item.range +'</span></div><div class="small desc">'+ item.name +'</div><div class="rel flex flex-align mb5 price-box"><span class="small red bold">{pigcms{$config["Currency_symbol"]}</span><span class="red now-price">'+ item.price +'</span><span class="small gray old-price">门市价：{pigcms{$config["Currency_symbol"]}'+ item.old_price +'</span><span class="small right mt5">月售'+ item.sale_count +'</span></div>' + (item.info != '' ? '<div class="discount-icon"><span class="icon">'+ item.info +'</span></div>' : '') + '</div></div></div>'

								}
								if (item.re_type == 'pin_group') {
									item_html += '</div><div class="info-box"><div class="flex flex_justify name-box"><span class="name"><span class="mr5">'+ item.store_name +'</span><span class="food-icon">拼团</span></span><span class="small nowrap">'+ item.range  +'</span></div><div class="small desc">'+ item.name +'</div><div class="rel flex flex-center price-box mb2"><span class="small red bold">{pigcms{$config["Currency_symbol"]}</span><span class="red now-price">'+ item.price +'</span><span class="small gray old-price">门市价：{pigcms{$config["Currency_symbol"]}'+ item.old_price +'</span></div><div class="news rel"><div class="swiper-container slider-goods mt5"><div class="swiper-wrapper"><div class="swiper-slide"><span class="desc red">已拼'+ item.sale_count +'件</span></div><div class="swiper-slide"><span class="desc red">'+ item.sale_person +'人正在拼团</span></div></div></div><div class="news-mask"></div></div></div></div>'
								}



								$('.list-wrapper').append(item_html)
							}
						}

                        console.log('是否显示标题', page, data.result)
                        if (page == 1 && data.result.list.length > 0) {
                        	$('.shop-list-title, .list-wrapper').removeClass('hidden');
                        }
						if (data.result.list.length == 10) {
							page++;
							$('.bottom-txt .loading').removeClass('hidden');
						} else {
							complete = true;
							$('.bottom-txt .loading').addClass('hidden');
							$('.bottom-txt .complete').removeClass('hidden');
						}
						
						swiper_goods = new Swiper('.slider-goods.swiper-container', {
								    	direction: 'vertical',
								    	autoplay: 4000,
								    	loop:true,
								        observer:true,
								        observeParents:true
								    });
				})
			}

			// 超值优惠商品点击上报
			function buyDiscountReport (id) {
				var param = JSON.parse(JSON.stringify(request_param));
				param.id = id
				$.post('/appapi.php?c=Home&a=plat_buy_active_up', param)
			}

			// 获取页面广告数据
			function getHomeData () {
				$.post('/appapi.php?c=Home&a=index_version_two', request_param, function(data){
    				if (data.result) {

    					if (!data.result.mobile_index_tpl) {
    						data.result.mobile_index_tpl = 'pure'
    					}
    					$('.'+ data.result.mobile_index_tpl + '-header').removeClass('hidden');
    					$('.switch-btn .' + data.result.mobile_index_tpl).removeClass('hidden');
    					$('.search-wrapper').attr('class', 'search-wrapper '+data.result.mobile_index_tpl);
    					$('.search-wrapper.default').css('background',mobile_color);
    					$('.search-wrapper.pure').css('background','');
    					

    					// 搜索框最右边的更多图标
    					if (data.result.plat_index_more_slider.length > 0) {
    						var more_html = '';
    						for (var i=0; i<data.result.plat_index_more_slider.length; i++) {
    							var item = data.result.plat_index_more_slider[i];
    							more_html += '<view class="item rel border-b link" data-url="'+ item.url +'"><img src="'+ item.pic +'">'+ item.name +'</view>'
    						}
    						$('.search-wrapper .more-mask').append(more_html);
    					} else {
    						$('.search-wrapper .more.icon').addClass('hidden');
    					}

    					// 标准版四个图标按钮
    					if (data.result.app_index_top_btn_slider.length > 0) {
    						var btn_nav_html = ''
    						for (var i=0; i<data.result.app_index_top_btn_slider.length; i++) {
    							var item = data.result.app_index_top_btn_slider[i];
    							btn_nav_html += '<div class="item link" data-url="'+ item.url +'"><div><img src="'+ item.pic +'"></div><div>'+ item.name +'</div></div>'
    						}
    						$('.default-header .btn-nav').html(btn_nav_html);
    					} else {
    						$('.default-header .btn-nav').addClass('hidden');
    					}
    					
    					// 标准版小轮播
    					if (data.result.app_index_top_adver_small.length > 0) {
    						var samll_html = '';
    						for(var i = 0; i < data.result.app_index_top_adver_small.length; i++) {
    							samll_html += '<div class="swiper-slide link" data-url="'+data.result.app_index_top_adver_small[i].url+'"><img src="'+ data.result.app_index_top_adver_small[i].pic +'"></div>'
    						}
    						$('.default-header .slider-small .swiper-wrapper').html(samll_html);
    						if(data.result.app_index_top_adver_small.length > 1){
    							var swiper_slider_small = new Swiper('.swiper-container.slider-small', {
							    	pagination: '.slider-small .swiper-pagination',
							        paginationClickable: true,
							        observer:true,
							        observeParents:true,
							        autoplay: 3000,
							    	loop:true,
							    	autoplayDisableOnInteraction : false
							    });
    						}
    					} else {
    						$('.default-header .slider-small').addClass('hidden');
    					}


    					// 清新版顶部大轮播
    					if (data.result.app_index_top_adver_big.length > 0) {
    						var big_slider_html = '';
    						for(var i=0; i<data.result.app_index_top_adver_big.length; i++) {
    							var item = data.result.app_index_top_adver_big[i];
    							big_slider_html += '<div class="swiper-slide link" data-url="'+ item.url +'"><img src="'+ item.pic +'"></div>';
    						}
    						$('.pure-header .swiper-wrapper').html(big_slider_html);
    						if(data.result.app_index_top_adver_big.length > 1) {
    							var swiper_slider_big = new Swiper('.pure-header .swiper-container', {
									pagination: '.pure-header .swiper-pagination',
							        paginationClickable: true,
							        observer:true,
							        observeParents:true,
							        autoplay: 3000,
							    	loop:true,
							    	autoplayDisableOnInteraction : false
							    });
    						}
    						// 清新版轮播图 640*338
		    				$('.pure-header').height( width * 338 / 640 );
    					} else {
    						$('.pure-header .swiper-container, .pure-header .get-point').addClass('hidden');
    						$('.pure-header').css('height', '44px')
    					}

    					// 清新版用户购买信息滚动出现
    					if (data.result.scroll_msg.length > 0) {
    						var buy_html = '<img src="'+data.result.scroll_msg[0].avatar+'"><span>'+data.result.scroll_msg[0].nickname+data.result.scroll_msg[0].type_txt+'</span>';
					        $('.get-point').html(buy_html);
					        setTimeout(function(){
					        	$('.get-point').removeClass('active');
					        	$('.get-point').html('');
					            setUserBuy(1, data.result.scroll_msg)
					        }, 5000);
					    }

    					// 导航
    					if (data.result.slider.length > 0) {
    						var slider_arr = [];
    						var n = parseInt(data.result.slider.length / 10)
						    if (n == 0) {
						        slider_arr[0] = data.result.slider.slice(0);
						        $('.slider-box').css('padding-bottom', '15px');
						    } else {
						        for (var i = 0; i < n; i++) {
						          slider_arr[i] = data.result.slider.slice(i * 10, (i + 1) * 10);
						          if ((i + 1) == n && n * 10 < data.result.slider.length) {
						            slider_arr[n] = data.result.slider.slice(n * 10);
						          }
						        }
						    }

						    for (var i=0; i<slider_arr.length; i++) {
						    	var html = '<div class="swiper-slide flex flex-align flex-wrap">'
						    	for (var j=0; j<slider_arr[i].length; j++ ){
						    		html += '<div class="item link" data-url="'+ slider_arr[i][j].url +'"><div class="rel"><img src="'+ slider_arr[i][j].pic +'"></div>';
						    		if (slider_arr[i][j].mark_name != '') {
						    			html += '<span class="tip" style="background:'+ slider_arr[i][j].mark_bg +'">'+ slider_arr[i][j].mark_name +'</span>';
						    		}
						    		html += '<div>'+ slider_arr[i][j].name +'</div></div>';
						    		if (j == slider_arr[i].length-1 ) {
						    			html += '</div>'
						    			$('.slider-box .swiper-wrapper').append(html)
						    		}
						    	}
						    }

						    if(slider_arr.length > 1) {
						    	var swiper_slider_box = new Swiper('.swiper-container.slider-box', {
							    	pagination:'.slider-box .swiper-pagination',
							        paginationClickable: true,
							        observer:true,
							        observeParents:true
							    });
						    };
    					} else {
    						$('.slider-box').addClass('hidden');
    					}


    					// 平台快报
    					if (data.result.mobile_index_plat_news) {
    						var news_html = ''
	    					for (var i=0; i< data.result.news_list.length; i++) {
	    						news_html += '<div class="swiper-slide flex flex-align "><div class="label">'+ data.result.news_list[i].cat_name +'</div><div class="txt">'+ data.result.news_list[i].title +'</div></div>'
	    					}
	    					$('.new-list .swiper-wrapper').html(news_html);
	    					var swiper_news = new Swiper('.new-list .swiper-container', {
						    	direction: 'vertical',
						    	initialSlide :0,
						    	autoplay: 2000,
						    	loop:true,
						        observer:true,
						        observeParents:true
						    });
    					} else {
    						$('.new-list').addClass('hidden');
    					}

    					// 新人领券
    					if (data.result.entrance && data.result.entrance.dispaly == 1) {
    						$('.get_coupon .icon').attr('src', data.result.entrance.img)
    						$('.get_coupon .news-swiper').html(data.result.entrance.des)
    						$('.get_coupon .btn').html(data.result.entrance.name)
    						$('.get_coupon').data('url', data.result.entrance.url).removeClass('hidden')
    					}

    					
    					// 中间四张小图广告
    					if (data.result.app_index_center_four_adver.length > 0) {
    						var four_img_html = ''
    						for (var i=0; i<data.result.app_index_center_four_adver.length; i++){
    							var item = data.result.app_index_center_four_adver[i];
    							four_img_html +='<img class="link" data-url="'+ item.url +'" src="'+ item.pic +'">'
    						}
    						$('.four-img-advert').html(four_img_html);
    					} else {
    						$('.four-img-advert').addClass('hidden');
    					}

    					// 中间小轮播
    					if (data.result.app_index_center_adver.length > 0) {
    						var slider_center_html = '';
    						for (var i=0; i<data.result.app_index_center_adver.length; i++) {
    							var item = data.result.app_index_center_adver[i];
    							slider_center_html += '<div class="swiper-slide link" data-url="'+ item.url +'"><img src="'+ item.pic +'"></div>';
    						}
    						$('.slider-center .swiper-wrapper').html(slider_center_html);
    						if(data.result.app_index_center_adver.length > 1) {
    							var swiper_slider_center = new Swiper('.swiper-container.slider-center', {
							    	pagination: '.slider-center .swiper-pagination',
							        paginationClickable: true,
							        observer:true,
							        observeParents:true,
							        autoplay: 2000,
					    			loop:true,
					    			autoplayDisableOnInteraction : false
							    });
    						}
    					} else {
    						$('.slider-center').addClass('hidden');
    					}

					    // 超值抢购
					    if (data.result.plat_buy_active_info.have) {
					    	$('.buy-discount-info .title-box .title').html(data.result.plat_buy_active_info.name);
					    	countDown(data.result.plat_buy_active_info.end_time);
					    	var activity_html = '';
					    	for (var i=0; i<data.result.plat_buy_active_info.active_list.length; i++) {
					    		var item = data.result.plat_buy_active_info.active_list[i];
					    		activity_html += '<div class="goods-item link" data-url="'+item.active_link +'" data-goodsid="'+ item.id +'"><div class="img-box"><img class="activity-img" src="'+ item.active_img +'">';
					    		if(item.active_label != '') {
					    			activity_html += '<span class="icon">'+ item.active_label +'</span>';
					    		}
					    		activity_html += '</div><div class="name">'+ item.active_name +'</div><div class="desc">'+ item.active_desc +'</div><div class="price-box">'+ currency_symbol+item.active_price;
					    		if(item.active_price*1 <= item.old_price*1){
					    			activity_html += '<span class="old-price">'+currency_symbol+ item.old_price +'</span>'
					    		}
					    		activity_html += '</div></div>'
					    	}
					    	$('.buy-discount-info .goods-box').html(activity_html);

					    } else {
					    	$('.buy-discount-info').addClass('hidden');
					    }

					    // 大牌臻选
					    if (data.result.plat_five_adver_info.length > 0) {
					    	for (var i=0; i<data.result.plat_five_adver_info.length; i++) {
					    		var list = data.result.plat_five_adver_info[i];
					    		var five_html = '<div class="flex flex_justify flex-align title-box"><span class="title">'+ list.group_name +'</span>';
					    		if ( list.group_link && list.group_link != "" ) {
					    			five_html += '<div class="more link" data-url="'+ list.group_link +'">更多<span class="icon iconfont">&#xe601;</span></div></div>';
					    		} else {
					    			five_html += '</div>'
					    		}
					    		five_html += '<div class="flex flex-wrap brand-box"><div class="brand-img link" data-url="'+ list.group_pic_link +'"><img src="'+ list.group_pic +'"></div>';
					    		for (var j=0; j<list.adver_list.length; j++) {
					    			var item = list.adver_list[j];
					    			if (list.group_from == 1) {
					    				five_html += ' <div class="goods-item link" data-url="'+ item.active_link +'"><div class="img-box just-img"><img src="'+ item.active_img +'"></div></div>';
					    			} else {
					    				five_html += ' <div class="goods-item link" data-url="'+ item.active_link +'"><div class="img-box"><img src="'+ item.active_img +'">';
					    				if (item.active_label != "") {
					    					five_html += '<span class="icon">超值</span>';
					    				} 

					    				five_html += '</div><div class="name">'+ item.active_name +'</div><div class="desc">'+ item.active_desc +'</div><div class="price-box"><span class="price">'+currency_symbol+item.active_price+'</span>';

					    				if (item.old_price*1 >= item.active_price*1) {
					    					five_html += '<span class="old-price">'+ currency_symbol+item.old_price +'</span></div></div>';
					    				} else {
					    					five_html += '</div></div>';
					    				}
					    			}
					    		}
					    		$('.five-box').append(five_html);
					    	}
					    } else {
					    	$('.five-box').addClass('hidden');
					    }

					    // 猜你喜欢的标题
					    if (data.result.index_guess_like_title) {
					 	   $('.shop-list-title .title').html(data.result.index_guess_like_title);
					    }
					    if (data.result.index_guess_like_desc) {
					    	$('.shop-list-title .desc').html(data.result.index_guess_like_desc);
					    }
					    var width = $(window).width();
		    			// 导航图片
		    			$('.slider-box .item img').height($('.slider-box .item img').width());
		    			// 标准版顶部轮播 688*150
		    			$('.default-header .slider-small').height( (width - 20) * 150 / 688 );
		    			// 平铺4张小图广告图 160*105
		    			$('.four-img-advert img').height((width - 20) * 0.23 * 105 / 160);
		    			// 中间栏轮播广告图 690*150
		    			$('.slider-center').height( (width - 20) * 150 / 690 );
		    			console.log($('.pure-header .swiper-container').height())
		    			// 五张广告图
		    			$('.brand-box .img-box.just-img, .brand-box .brand-img').height(((width - 10) * 0.333333 - 10) * 343 / 223)
    					
    				}
    			})
			}

			// 超值抢购倒计时
			function countDown (s) {
			  var activity_time = timestampToTime(s);
			  var time_html = ''
			  if (activity_time.day > 0) {
			  	time_html += '<span class="time">'+ activity_time.day +'</span>天';
			  }
			  time_html += '<span class="time">'+ activity_time.hours +'</span>:<span class="time">'+ activity_time.min +'</span>:<span class="time">'+ activity_time.sec +'';
			  $('.buy-discount-info .title-box .time-box').html(time_html);
			  if (s == 0) {
			    return
			  }
			  setTimeout(function() {
			    s--
			    countDown(s)
			  }, 1000)
			}

			// 时间转换
			function timestampToTime (s) {
			  var day = Math.floor(s / 3600 / 24);
			  var hours = Math.floor(s / 3600 % 24);
			  var min = Math.floor(s / 60) % 60;
			  var sec = s % 60;
			  hours = add(hours);
			  min = add(min);
			  sec = add(sec);
			  return { day, hours, min, sec }
			}

			// 添 0
			function add(m) {
			  return m < 10 ? '0' + m : m
			}

			// 定时显示用户购买信息
			function setUserBuy (num, msg) {
			  setTimeout(function() {
			    if (num < msg.length) {
		    		var buy_html = '<img src="'+msg[num].avatar+'"><span>'+msg[num].nickname+msg[num].type_txt+'</span>';
			        $('.get-point').html(buy_html);
			      	$('.get-point').addClass('active');
			      setTimeout(function() {
			      	$('.get-point').html('');
			        $('.get-point').removeClass('active');
			        num++
			        setUserBuy(num, msg)
			      }, 5000)
			    } else {
			      $('.get-point').html('');
			    }
			  }, 2000)
			}
			
			function getBaiduLocation(locationStr,lng,lat) {
				console.log('定位结果', locationStr, lng, lat)
				geoconv('getBaiduLocationAfter', lng, lat)
				if (!lng) {
					getList();
				}
			}
			
			function getBaiduLocationAfter(result) {
				console.log('转码为百度坐标111',result);
				user_long = result.result[0].x
				user_lat = result.result[0].y
				getList();
				geocoder('locationCity',result.result[0].x,result.result[0].y);
			}
			
			function locationCity(result, lng, lat){
				if (result) {
					user_long = result.result.location.lng
					user_lat = result.result.location.lat
				} else {
					user_long = lng
					user_lat = lat
				}
				console.log('转码为百度坐标',result);
				if(result.result && result.result.addressComponent && result.result.addressComponent.city && !$.cookie('is_location_city_new')){	
					var city_name = result.result.addressComponent.city;
					var area_name = result.result.addressComponent.district;
					city_name = city_name.replace('市','');		
			 		
					if(location_mode == 1){
						// 开启定位到商场（客户定制）
						var lat = result.result.location.lat;
						var lng = result.result.location.lng;
						var send_data = {city_name:city_name,area_name:area_name,lat:lat,lng:lng};
					}else{
						// 开启定位到区县
						var send_data = {city_name:city_name,area_name:area_name};
					}
					$.post('./wap.php?c=Home&a=cityMatching',send_data,function(res){
						console.log('6666666666',res);
						if((res.status == 1 && location_mode == 0) || (res.status == 1 && location_mode == 1)){
							$.cookie('is_location_city_new','1',{path:'/'});
							
							// 不弹层，直接切换到当前定位
							if (res.info.area_name){
								var oldCity = request_param['now_city'];
								$.cookie('now_city',res.info.city_id,{expires:120,path:'/'});
								$.cookie('now_area',res.info.area_id,{expires:120,path:'/'});
								$('.address span').html(res.info.area_name);
								if(oldCity != res.info.city_id){
									request_param['now_city'] = res.info.city_id
									page = 1;
									complete = false;
									getCoupon();
									getHomeData();
									getList();
								}
							}
						}
					});
				} 
			}
    	</script>
		<if condition="$_GET['viewpage']">
			<script type="text/javascript">
				$(function(){
					$('body').append('<div style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:99999;"></div>');
					$('body').append('<style>::-webkit-scrollbar{width: 6px;height: 8px;background-color: #f6f8f8;-webkit-border-radius: 4px;-moz-border-radius: 4px;border-radius: 4px;}::-webkit-scrollbar-thumb {background-color:rgba(45, 41, 41, 0.5);-webkit-border-radius: 4px;-moz-border-radius: 4px;border-radius: 4px;}</style>');
				});
			</script>
		</if>
		{pigcms{$shareScript}
		<script type="text/javascript">
			window.shareData = {
				"moduleName":"Home",
				"moduleID":"0",
				"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
				"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Home/index')}",
				"tTitle": "{pigcms{$config.site_name}",
				"tContent": "{pigcms{$config.seo_description}"
			};
		</script>
	</body>
</html>