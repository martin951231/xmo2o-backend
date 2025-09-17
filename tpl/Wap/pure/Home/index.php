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
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?21923"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/index.css?1"/>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/idangerous.swiper.min.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?2112333" charset="utf-8"></script>
		<script type="text/javascript">var group_index_sort_url="{pigcms{:U('Home/group_index_sort')}";var location_mode={pigcms{$config.location_mode|intval=###};<if condition="$user_long_lat">var user_long = "{pigcms{$user_long_lat.long}",user_lat = "{pigcms{$user_long_lat.lat}";<else/>var user_long = '0',user_lat  = '0';</if>var app_version="{pigcms{$_REQUEST['app_version']}"</script>
		<script type="text/javascript" src="{pigcms{$static_path}js/index.js?212" charset="utf-8"></script>
		<if condition="$config.guess_content_type eq 'shop'">
			<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}shop/css/home_shop.css?216"/>
		<elseif condition="$config.guess_content_type eq 'meal'" />
			<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/home_meal.css?216"/>
		</if>
		<script>
			var guess_num	=	"{pigcms{$guess_num}";
			var guess_content_type	=	"{pigcms{$guess_content_type}";
			var merchant_around_url  = "{pigcms{:U('Merchant/around')}";
			var payqrcode_url  = "{pigcms{:U('My/pay_qrcode')}";
			var scan_order_url  = "{pigcms{:U('My/scan_order')}", deliverName = "{pigcms{$config['deliver_name']}";
		</script>
        <style>
            body{
                background: #F4F4F4;

            }
            *{margin: 0;padding: 0;}
            .ft{
                float:left;
            }
            .rg{
                float:right;
            }
            .clear:after{
                content: " ";
                display:block;
                clear: both;
            }
            ul{list-style:none;}
            .hrash{background: #fff;width: 100%;    position: relative;}
            .left_scroll{
                overflow-x: scroll;
                position: relative;
            }
            .hrash ul{
                width: 660px;
            }
            .hrash ul li{
                padding: 10px 0;
                width: 80px;
                font-size: 14px;
                text-align: center;
                border-top: 1px solid #f1f1f1;
                border-right: 1px solid #f1f1f1;
                color: #666;
            }
            .hrash ul li.active{
                background: #FE5842;
                color: #fff;
                border-top: 1px solid #FE5842;
                border-right: 1px solid #FE5842;
            }
            .hrash p{
                position: absolute;
                right: 0;
                top: 0;
                background: #fff;
                padding: 10px 0;
                width: 60px;
                font-size: 14px;
                text-align: center;
                border-left: 1px solid #eee;
                color: #06C1AE;
            }
            .bg_mask{
                background: rgba(0,0,0,.8);
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                display: none;
            }
            .bg_mask ul{
                margin-top: 30px;
            }
            .bg_mask ul li{
                width: 25%;
                text-align: center;
                color: #fff;
                padding: 10px 0;
            }
            .bg_mask ul li.active{
                color: #FE5842;
            }
            .bg_mask .close{
                font-size: 50px;
                color: #fff;
                position: fixed;
                bottom: 10px;
                width: 100%;
                text-align: center;
            }
            .picadds{
                width: 100%;
            }
            .picContent{
                overflow-x: scroll;
            }
            .picContent ul{
                width: 440px;
                font-size: 14px;
				-webkit-overflow-scrolling:touch;
            }
            .picContent ul li{
                width: 130px;
                background: #fff;
                padding:10px 10px 5px   10px;
                margin: 10px 0px 10px 10px;

            }
            .picContent ul li dt img{
                width: 130px;height: 130px;

            }
            .picContent ul li .sizes{
                width: 100%;
                overflow: hidden;
                text-overflow:ellipsis;
                white-space: nowrap;
                text-align: center;
                font-size: 14px;
                color: #333;
            }
            .picContent ul li .high{
                font-size: 12px;
                color: #999;
                padding: 2px 0;
            }
            .picContent ul li .high i{
                display: inline-block;
                width: 12px;
                height: 12px;
                background: url({pigcms{$static_path}images/z22.png) center no-repeat;
                background-size: contain;
                margin-right: 2px;
            }
            .picContent ul li .flexss{
                display: flex;
                -webkit-box-pack: justify;
                -webkit-justify-content: space-between;
                justify-content: space-between;
                -webkit-box-align: center;
                -webkit-align-items: center;
                align-items: center;

            }
            .picContent ul li .flexss>span{
                font-size: 16px;
                color: #990033;
                font-weight: 700;
            }
            .picContent ul li .flexss>span>span{
                font-size: 12px;
            }
            .picContent ul li .flexss p{
                font-size: 12px;
                color: #666;
            }

			.index_house{
				position:relative;
			}
			.index_house:after {
				display: block;
				content: "";
				border-top: 1px solid #BFBFBF;
				border-left: 1px solid #BFBFBF;
				width: 8px;
				height: 8px;
				-webkit-transform: rotate(135deg);
				background-color: transparent;
				position: absolute;
				top: 50%;
				right: 15px;
				margin-top: -5px;
			}
			.list_content .clear:after{
				content: " ";
				display: block;
				clear: both;
			}
			.mr50{right:50px !important;left:auto !important;}
			#select{
				background: url(../img/index/qrcode.png) no-repeat;
				position: absolute;
				width: 50px;
				height: 100%;
				top: 0;
				right: 0;
				background-size: 19px 20px;
				background-position: 16px 15px;
			}
			#select ul{
				 right: 0px;
				 top: 50px;
				 position: absolute;
				 background: #fff
			 }
			#select ul li{
				height: 40px;
				text-align: center;
				border-bottom:1px solid #ddd;
				line-height:40px
			}
			.weui-cell {
			padding:0px 15px;
				position:relative;
				display:-webkit-box;
				display:-webkit-flex;
				display:flex;
				-webkit-box-align:center;
				-webkit-align-items:center;
				align-items:center;
				height:45px;
				line-height:45px;


			}
			.weui-cell_access {
		-webkit-tap-highlight-color:rgba(0,0,0,0);
				color:inherit;

			}

			.weui-cell_primary {
			-webkit-box-align:start;
				-webkit-align-items:flex-start;
				align-items:flex-start;

			}
			.weui-cell__bd {
			-webkit-box-flex:1;
				-webkit-flex:1;
				flex:1;

			}
			.weui-cells {
			margin-top:0px;
				background-color:#fff;
				line-height:1.47058824;
				font-size:28rpx;
				overflow:hidden;
				position:relative;

			}

			.weui-cell__hd img {
				height: 26px;
				width: 26px;
				margin-right: 5px;
				display: block;

			}
			.weui-cell_access .weui-cell__ft:after {
				content: " ";
				display: inline-block;
				height: 6px;
				width: 6px;
				border-width: 2px 2px 0 0;
				border-color: #c8c8cd;
				border-style: solid;
				-webkit-transform: matrix(.71,.71,-.71,.71,0,0);
				transform: matrix(.71,.71,-.71,.71,0,0);
				position: relative;
				top: -2px;
				position: absolute;
				top: 50%;
				margin-top: -4px;
				right: 2px;
			}
			.weui-cells:after {
				bottom: 0;
				border-bottom: 1px solid #e5e5e5;
				-webkit-transform-origin: 0 100%;
				transform-origin: 0 100%;
				-webkit-transform: scaleY(.5);
			}
			.weui-cell:before {
				content: " ";
				position: absolute;
				left: 0;
				top: 0;
				right: 0;
				height: 1px;
				border-top: 1px solid #e5e5e5;
				color: #e5e5e5;
				-webkit-transform-origin: 0 0;
				transform-origin: 0 0;
				-webkit-transform: scaleY(.5);
				transform: scaleY(.5);
				left: 15px;
				z-index: 2;
			}
			.mr_50{left:auto !important;right:30px !important;}
			.likeBox .img_box{
				width:56px;
				height:56px;
				border-radius: 28px;
				float: left;
				overflow:auto;
				margin:13.5px 10px 13.5px 0;
			}
			.likeBox .img_box img{
				width:100%;
				height:100%;
				display: block;
				object-fit:cover;
			}
			.likeBox dl.on .img_box img{
				/*-webkit-filter: grayscale(100%);
			    filter: grayscale(100%);*/
			    opacity: 0.6;
			}
			.Notopen {
			    font-size: 12px;
			    color: #e6454a;
			    border-radius: 50px;
			    border: #e6454a 1px solid;
			    text-align: center;
			    line-height: 17px;
			    padding: 0 5px;
			    margin: -2px 0 0px 5px;
			}
            .star i.full {
                background-image: url(./tpl/Wap/pure/static/shop/images/full_star.png);
            }
            .star i {
                width: 12px;
                height: 12px;
                display: inline-block;
                background: url(./tpl/Wap/pure/static/shop/images/none_star.png) no-repeat;
                background-size: 100%;
                margin-right: 2px;
            }
            .star i.half {
                background-image: url(./tpl/Wap/pure/static/shop/images/mid_star.png);
            }
		</style>
	</head>
	<body>
		<header <if condition="$config['many_city']">class="hasManyCity"</if> style="z-index:111;<if condition='$config["mobile_head_color"]'>background-color: {pigcms{$config["mobile_head_color"]};</if>">
			<if condition="$config['many_city']">
				<div id="cityBtn" class="link-url" data-url="{pigcms{:U('Changecity/index')}" style='max-width:120px'><if condition="$now_select_area">{pigcms{:msubstr($now_select_area['area_name'],0,5)}<else/>{pigcms{:msubstr($now_select_city['area_name'],0,5)}</if>
				</div>



		          <div id="searchBox" data-ff="2" style="<if condition="$config['house_open'] && $config['house_position'] eq 2">margin-left: 0px	<else/> margin-right:45px;margin-left: 0px	</if>">
					<a href="{pigcms{:U('Search/index')}" <if condition="$is_weixin_browser">style='float: left;width: 80%;'</if>>
						<i class="icon-search"></i>
						<span>请输入您想找的内容</span>
						<if condition="$is_weixin_browser">	<a href="javascript:;" style="width:20%;float:right;" class="smallVioce"><i class="icon-hua"></i></a></if>
					</a>
				</div>
		   <else />
				<div id="cityBtn" class="link-url" data-name="{pigcms{$config['now_select_city']['area_name']}" data-url="{pigcms{:U('Changecity/index')}" data-top_domain="{pigcms{$config.many_city_top_domain}" style='max-width:120px'>
		         	<if condition="$now_select_area">{pigcms{:msubstr($now_select_area['area_name'],0,5)}<else/>{pigcms{:msubstr($now_select_city['area_name'],0,5)}</if>
	            </div>
				<if condition="msubstr($config['now_select_city']['area_name'],0,2) neq $config['now_select_city']['area_name'] && $config['house_open'] && $config['house_position'] eq 2">
				   <elseif condition="msubstr($config['now_select_city']['area_name'],0,2) neq $config['now_select_city']['area_name']"/>
				   <div id="locaitonBtn" class="link-url mr_50" data-url="{pigcms{:U('Merchant/around')}"></div>
			   </if>

				<div id="searchBox" data-ff="23"  style="<if condition="msubstr($config['now_select_city']['area_name'],0,2) neq $config['now_select_city']['area_name'] && $config['house_open'] && $config['house_position'] eq 2">margin: 10px 50px 0px 0px;<elseif condition="msubstr($config['now_select_city']['area_name'],0,2) neq $config['now_select_city']['area_name']"/>margin: 10px 77px 0px 0px;<else/> margin-right:49px;margin-left: 0px</if>">
					<a href="{pigcms{:U('Search/index')}" <if condition="$is_weixin_browser">style='float: left;width: 80%;'</if>>
						<i class="icon-search"></i>
						<span>请输入您想找的内容</span>
						<if condition="$is_weixin_browser">
							<a href="javascript:;" style="width: 20%; float: right;" class="smallVioce"><i class="icon-hua"></i></a>
						</if>
					</a>
				</div>
           </if>

			<if condition="$config.open_score_fenrun eq 1 AND $config.fenrun_btn_location eq 1">
			<div class="More_drop"></div>
			<div class="drop_list">
				<ul>
					<if condition="$config['many_city']"><li class="nearby"><span>附近商户</span></li></if>
					<li class="scan qrcodeBtn"  ><span>扫一扫</span></li>
					<li class="payment"><span>付款码</span></li>
				</ul>
			</div>
			<elseif condition="$config.open_score_fenrun neq 1" />
			<if condition="$config['house_open'] && $config['house_position'] eq 2">
			<div class="More_drop"></div>
			<div class="drop_list slider">
				<ul>
					<li class="location" data-url="{pigcms{:U('Merchant/around')}"><span>附近店铺</span></li>
					<li class="scan qrcodeBtn"  ><span>扫一扫</span></li>
					<li class="my_order house_btn" data-url="{pigcms{:U('House/village_list')}"><span>我的{pigcms{$config.house_name}服务</span></li>
				</ul>
			</div>
			<else/>
			   <div id="qrcodeBtn" class="qrcodeBtn"></div>
			</if>
			</if>

			<if condition="$config.open_score_fenrun eq 1">
			<div class="payment_code" >
				<div class="h2">向商家付款</div>
				<div class="h3">该功能用于向商家当面付款</div>
				<div class="con_img">
					<img id="paybarcode" src="">
					<img id="payqrcode"  src="">
					<div class="refresh">
						<span>每分钟自动刷新</span>
					</div>
				</div>
			</div>
			</if>
			<div class="del"></div>
			<div class="mask"></div>
		</header>



		<div id="container" style="top:50px;-webkit-transform:translate3d(0,0,0)">
			<div id="scroller">
				<div id="pullDown">
					<span class="pullDownIcon"></span><span class="pullDownLabel">下拉可以刷新</span>
				</div>
				<if condition="$wap_index_top_adver">
					<section id="banner_hei" class="banner">
						<div class="swiper-container swiper-container1">
							<div class="swiper-wrapper">
								<volist name="wap_index_top_adver" id="vo">
									<div class="swiper-slide">
										<a href="{pigcms{$vo.url}">
											<img src="{pigcms{:thumb_img($vo['pic'],640,238)}"/>
										</a>
									</div>
								</volist>
							</div>
							<div class="swiper-pagination swiper-pagination1"></div>
						</div>
					</section>
				</if>
				<if condition="$config['open_score_fenrun'] eq 1 AND $config['fenrun_btn_location'] eq 2">
				<div class="nav_bar">
					<ul>
						<li>
							<a href="javascript:void(0)"  class="qrcodeBtn">
								<i>
									<img src="{pigcms{$static_path}img/index/fenrun1.png">
								</i>
								<span>扫一扫</span>
							</a>
						</li>
						<li>
							<a href="javascript:void(0)" class="payment">
								<i>
									<img src="{pigcms{$static_path}img/index/fenrun2.png">
								</i>
								<span>付款码</span>
							</a>
						</li>
						<li>
							<a href="{pigcms{:U('Fenrun/user_free_award_list')}">
								<i>
									<img src="{pigcms{$static_path}img/index/fenrun4.png">
								</i>
								<span>佣金</span>
							</a>
						</li>
						<li>
							<a href="{pigcms{:U('Fenrun/fenrun_money_list')}">
								<i>
									<img src="{pigcms{$static_path}img/index/fenrun3.png">
								</i>
								<span>分润钱包</span>
							</a>
						</li>

					</ul>
				</div>
				</if>


				<if condition="!empty($scroll_msg)">
					<div style=" height: 16px; margin-bottom: 10px; padding: 10px 15px;background: #ffffff;" class="scroll_msg" >
						<div style="background: url({pigcms{$static_path}images/lbt_03.png) left 1px no-repeat; background-size: 14px; padding-left: 20px;">
							<div class=""  id="scrollText" style="border-left: #cfcfcf 1px solid; padding-left: 8px; font-size: 12px; height: 15px;">
								<marquee  style="line-height: 16px;  white-space: nowrap;  " scrolldelay="100">
									<volist name="scroll_msg" id="vo">
										<div style="display:inline-block">
											<span style="padding-right:20px;color:#ff2c4d;">
												<a>{pigcms{$vo.content}</a>
											</span>
										</div>
									</volist>
								</marquee>
							</div>
						</div>
					</div>
					<style>
					#scrollText div a{ color: #ff2c4d;}
					</style>
					<link rel="stylesheet" href="{pigcms{$static_public}font-awesome/css/font-awesome.min.css">
				</if>

				<if condition="$config['house_open'] && ($config['house_position'] eq 1 || !$config['house_position'])">
					<section class="invote index_house" style="margin-top:10px;<if condition="!empty($scroll_msg)">margin-bottom:0px</if>">
						<a href="{pigcms{:U('House/village_list')}">
							<img src="{pigcms{$config.wechat_share_img}"/>
							我的{pigcms{$config.house_name}服务
						</a>
					</section>
				</if>  
				<if condition="$wap_index_center_slider_adver_header">
				<section class='slider'>
					<div class="swiper-container swiper-containerSlide1">
						<div class="swiper-wrapper slideImg">
							<volist name = "wap_index_center_slider_adver_header" id="vo">
								<div class="swiper-slide">
									<a href="{pigcms{$vo.url}">
										<img src="{pigcms{$vo.pic}">
									</a>
								</div>
							</volist>
						</div>
						<div class="swiper-pagination swiper-paginationSlide1"></div>
					</div>
				</section>
				</if>

				<if condition="$wap_index_slider or $news_list">
					<section class="slider">
						<if condition="$wap_index_slider">
                            <div class="swiper-container swiper-container2" <php>if (count($wap_index_slider) >1) {</php>style="height:168px;"<php>}else{</php>style="height:90px;"<php>}</php>>
							<div class="swiper-wrapper">
								<volist name="wap_index_slider" id="vo">
									<div class="swiper-slide">
										<ul class="icon-list num{pigcms{$wap_index_slider_number}">
											<volist name="vo" id="voo">
												<li class="icon">
													<a href="{pigcms{$voo.url}">
														<span class="icon-circle">
															<img src="{pigcms{:thumb_img($voo['pic'],200)}"/>
														</span>
														<span class="icon-desc">{pigcms{$voo.name}</span>
													</a>
												</li>
											</volist>
										</ul>
									</div>
								</volist>
							</div>
							<div class="swiper-pagination swiper-pagination2"></div>
						</div>
						</if>
						<if condition="$news_list">
							<div class="platformNews clearfix link-url" data-url="{pigcms{:U('Systemnews/index')}">
								<div class="left ico"></div>
								<div class="left list">
									<ul>
										<volist name="news_list" id="vo">
											<li class="num-{pigcms{$i}" <if condition="$i gt 2">style="display:none;"</if>>[{pigcms{$vo.name}] {pigcms{$vo.title}</li>
										</volist>
									</ul>
								</div>
							</div>
						</if>
					</section>
				</if>
				<if condition="$invote_array">
					<section class="invote">
						<a href="{pigcms{$invote_array.url}">
							<img src="{pigcms{:replace_file_domain($invote_array['avatar'])}"/>
							{pigcms{$invote_array.txt}
							<button>关注我们</button>
						</a>
					</section>
				<elseif condition="$share"/>
					<section class="invote">
						<a href="{pigcms{$share.a_href}">
							<if condition="$share['image']">
								<img src="{pigcms{:replace_file_domain($share['image'])}"/>
							</if>
							{pigcms{$share.title}
							<button>{pigcms{$share['a_name']}</button>
						</a>
					</section>
				</if>
				<if condition="$activity_list">
					<section class="activity">
						<div class="activityBox">
							<div class="swiper-container swiper-container4">
								<div class="swiper-wrapper">
									<volist name="activity_list" id="vo">
										<div class="swiper-slide">
											<a href="{pigcms{:U('Wapactivity/detail',array('id'=>$vo['pigcms_id']))}">
												<label>
													<span class="title">参与</span>
													<span class="number">{pigcms{$vo.part_count}</span>
												</label>
												<div class="clock"><span class="time_d">{pigcms{$time_array['d']}</span>天 <span class="timerBox"><span class="timer time_h">{pigcms{$time_array['h']}</span>:<span class="timer time_m">{pigcms{$time_array['m']}</span>:<span class="timer time_s">{pigcms{$time_array['s']}</span></span></div>
												<div class="icon">
													<img src="{pigcms{$vo.list_pic}" alt="{pigcms{$vo.name}"/>
												</div>
												<div class="desc">
													<div class="name">{pigcms{$vo.name}</div>
													<div class="price">
														<if condition="$vo['type'] eq 1">
															<strong class="yuan">剩{pigcms{$vo['all_count']-$vo['part_count']}</strong>
														<else/>
															<if condition="$vo['mer_score']">
																<strong>{pigcms{$vo.mer_score}{pigcms{$config['score_name']}</strong>
															<else/>
																<strong>{pigcms{$config['Currency_symbol']}{pigcms{$vo.money}</strong>
															</if>
														</if>
													</div>
												</div>
											</a>
										</div>
									</volist>
								</div>
							</div>
						</div>
					</section>
				</if>

				<if condition="$wap_index_house_adver">
					<section id="banner_house_adver" class="banner" style="margin-bottom:10px;background:white;">
						<div class="swiper-container swiper-containerhouse">
							<div class="swiper-wrapper">
								<volist name="wap_index_house_adver" id="vo">
									<div class="swiper-slide">
										<a href="{pigcms{$vo.url}">
											<img src="{pigcms{$vo.pic}"/>
											<p style="padding-left:10px;padding-top: 3px;font-size: 16px;">{pigcms{$vo.name}</p>
										</a>
									</div>
								</volist>
							</div>
							<div class="swiper-pagination swiper-paginationhouse"></div>
						</div>
					</section>
				</if>

				<php>if($wap_index_center_adver || $config['wap_around_show_type']==1){</php>
				<section class="recommend" style="height:auto;">
					<if condition="$wap_index_center_adver">
						<div class="recommendBox">
							<div class="recommendLeft link-url" data-url="{pigcms{$wap_index_center_adver.2.url}">
								<img src="{pigcms{$wap_index_center_adver.2.pic}" alt="{pigcms{$wap_index_center_adver.2.name}"/>
							</div>
							<div class="recommendRight">
								<div class="recommendRightTop link-url" data-url="{pigcms{$wap_index_center_adver.1.url}">
									<img src="{pigcms{$wap_index_center_adver.1.pic}" alt="{pigcms{$wap_index_center_adver.1.name}"/>
								</div>
								<div class="recommendRightBottom link-url" data-url="{pigcms{$wap_index_center_adver.0.url}">
									<img src="{pigcms{$wap_index_center_adver.0.pic}" alt="{pigcms{$wap_index_center_adver.0.name}"/>
								</div>
							</div>
						</div>
					</if>
					<if condition="$config['wap_around_show_type'] eq 1">
						<if condition="$wap_around">
						<div class="nearBox">
							<ul>
								<volist name="wap_around" id="vo">
									<li>
										<div class="nearBoxDiv merchant link-url" data-url="{pigcms{$vo.url}">
											<div class="title"><php>if($vo['name']!='merchant'){</php>附近<php>echo $config[$vo['name'].'_alias_name'];}else{</php>附近商家<php>}</php></div>
											<div class="desc" style="overflow: hidden;text-overflow: ellipsis;white-space: nowrap;">{pigcms{$vo.des}</div>
											<div class="icon" style="background-image:url({pigcms{:file_domain()}/upload/wap/{pigcms{$vo.pic})"></div>
										</div>
									</li>
								</volist>
							</ul>
						</div>
						<else />
						<div class="nearBox">
							<ul>
								<li>
									<div class="nearBoxDiv merchant link-url" data-url="{pigcms{:U('Merchant/around')}">
										<div class="title">附近商家</div>
										<div class="desc">快速找到商家</div>
										<div class="icon"></div>
									</div>
								</li>
								<li>
									<div class="nearBoxDiv group link-url" data-url="{pigcms{:U('Group/index')}">
										<div class="title">附近{pigcms{$config.group_alias_name}</div>
										<div class="desc">看得到的便宜</div>
										<div class="icon"></div>
									</div>
								</li>
								<li>
									<div class="nearBoxDiv store link-url" data-url="{pigcms{:U('Shop/index')}">
										<div class="title">附近{pigcms{$config.shop_alias_name}</div>
										<div class="desc">购物无需等待</div>
										<div class="icon"></div>
									</div>
								</li>
							</ul>
						</div>
						</if>
					</if>
				</section>
				<php>}</php>

				<if condition="$wap_index_center_slider_adver_middle">
				<section class='slider'>
					<div class="swiper-container swiper-containerSlide2">
						<div class="swiper-wrapper slideImg">
							<volist name = "wap_index_center_slider_adver_middle" id="vo">
								<div class="swiper-slide">
									<a href="{pigcms{$vo.url}">
										<img src="{pigcms{$vo.pic}">
									</a>
								</div>
							</volist>
						</div>
						<div class="swiper-pagination swiper-paginationSlide2"></div>
					</div>
				</section>
				</if>

				<if condition="$classify_Zcategorys">
					<section class="classify">
						<div class="headBox">{pigcms{$config.classify_name}</div>

						<div class="classifyBox">
							<div class="swiper-container swiper-container3">
								<div class="swiper-wrapper">
									<volist name="classify_Zcategorys" id="vo">
										<if condition="$vo['cat_pic']">
											<div class="swiper-slide">
												<a href="{pigcms{:U('Classify/index',array('cid'=>$vo['cid'],'ctname'=>urlencode($vo['cat_name'])))}#ct_item_{pigcms{$vo['cid']}">
													<span class="icon">
														<img src="{pigcms{:thumb($vo['cat_pic'],52)}"/>
													</span>
													<span class="desc">{pigcms{$vo.cat_name}</span>
												</a>
											</div>
										</if>
									</volist>
								</div>
							</div>
						</div>
					</section>
				</if>

				<if condition="$wap_index_center_slider_adver_footer">
				<section class='slider'>
					<div class="swiper-container swiper-containerSlide3">
						<div class="swiper-wrapper slideImg">
							<volist name = "wap_index_center_slider_adver_footer" id="vo">
								<div class="swiper-slide">
									<a href="{pigcms{$vo.url}">
										<img src="{pigcms{$vo.pic}">
									</a>
								</div>
							</volist>
						</div>
						<div class="swiper-pagination swiper-paginationSlide3"></div>
					</div>
				</section>
				</if>
				<section class="youlike hide">
					<div class="headBox">猜你喜欢</div>
					<dl class="likeBox dealcard"></dl>
				</section>
				<div class="content_details">
					<div id="recommend_list">
					</div>
					<div id="no_data" style="display: none; text-align: center; color: red; font-size: 18px; padding: 5px;">暂无数据</div>
				</div>

        <div class="allChild">

            <!--div class="hrash">
                <div class="left_scroll">
                    <ul class="clear">
                            <li class="ft active">{{ d[i][ii].name }}</li>
                    </ul>
                </div>
                <p class="moreClass">更多</p>
            </div>
            <div class="picadds">
                <div class="picContent">
                    <ul class="clear">
                        <li class="ft">
                            <dl>
                                <dt><img src="imanges/13-_10.png"/></dt>
                                <dd class="sizes">【正品】秋冬加绒卫衣</dd>
                                <dd class="high"><i></i>好评率95%</dd>
                                <dd class="flexss"><span><span>{pigcms{$config['Currency_symbol']}</span>109</span><p>266件已售</p></dd>
                            </dl>
                        </li>

                        <li class="ft">
                            <dl>
                                <dt><img src="imanges/13-_10.png"/></dt>
                                <dd class="sizes">【正品】秋冬加绒卫衣</dd>
                                <dd class="high"><i></i>好评率95%</dd>
                                <dd class="flexss"><span><span>{pigcms{$config['Currency_symbol']}</span>109</span><p>266件已售</p></dd>
                            </dl>
                        </li>
                        <li class="ft">
                            <dl>
                                <dt><img src="imanges/13-_10.png"/></dt>
                                <dd class="sizes">【正品】秋冬加绒卫衣</dd>
                                <dd class="high"><i></i>好评率95%</dd>
                                <dd class="flexss"><span><span>{pigcms{$config['Currency_symbol']}</span>109</span><p>266件已售</p></dd>
                            </dl>
                        </li>
                    </ul>
                </div>
            </div-->
        </div>
        <div class="bg_mask">
            <ul class="clear">
                <li class="ft active">手机数码</li>
                <li class="ft">品牌男装</li>
                <li class="ft">潮流女装</li>
                <li class="ft">电脑办公</li>
                <li class="ft">数码家电</li>
            </ul>
            <p class="close">×</p>
        </div>

				<script id="indexRecommendBoxTpl" type="text/html">
					<if condition="$config.guess_content_type eq 'group'">
					{{# for(var i = 0, len = d.length; i < len; i++){ }}
						<dd class="recommend-link-url" data-group_id="{{ d[i].group_id }}" data-url="{{ d[i].url }}">
							{{# if(d[i].pin_num > 0){ }}<div class="pin_style"></div>{{# } }}
							<div class="dealcard-img imgbox">
								<img src="{pigcms{$config.site_url}/index.php?c=Image&a=thumb&width=276&height=168&url={{ encodeURIComponent(d[i].list_pic) }}" alt="{{ d[i].s_name }}" style="height:auto;"/>
							</div>
							<div class="dealcard-block-right">
								<div class="brand"><div class="group_title" style="width:72%">{{ d[i].s_name }}</div>  {{# if(d[i].range){ }}<span class="location-right">{{ d[i].range }}</span>{{# } }}  </div>
								<div class="title">{{ d[i].intro }}</div>
								<div class="price">
									<strong>{{ d[i].price }}</strong><span class="strong-color">{pigcms{$config['Currency_txt']}{{# if(d[i].extra_pay_price!=''){ }}{{ d[i].extra_pay_price }}{{# } }}</span>{{# if(d[i].wx_cheap){ }}<span class="tag">微信再减{{ d[i].wx_cheap }}{pigcms{$config['Currency_txt']}</span>{{# }else{ }}<del>{{ d[i].old_price }}</del>{{# } }} <span class="line-right"> {{ d[i].sale_txt }}</span>
								</div>
							</div>
						</dd>
					{{# } }}
					<elseif condition="$config.guess_content_type eq 'shop'"/>

						{{# for(var i = 0, len = d.length; i < len; i++){ }}
							<dd class="recommend-link-url" data-url="./wap.php?c=Shop&a=classic_shop&shop_id={{ d[i].store_id }}" data-url-type="openRightFloatWindow" {{# if(d[i].is_close){ }}style="opacity:0.6;"{{# } }}>

								<div class="dealcard-img imgbox">
									{{# if(d[i].isverify == 1){ }}
										<img src="./static/images/kd_rec.png" style="    width: 41px;height: 15px;position: absolute;z-index: 99;margin: 2px 0 0 0;">
									{{# } }}
									<img style="margin-left: 0px;position: absolute;"  src="{{ d[i].image }}" alt="{{ d[i].name }}">
									{{# if(d[i].is_close){ }}<div class="closeTip">休息中</div>{{# } }}
								</div>
								<div class="dealcard-block-right">
									<div class="brand">{{ d[i].name }}<em class="location-right">{{# if(user_long != '0' && parseInt(d[i].juli_wx) < 10000000){ }}{{ d[i].range }}{{# } }}</em></div>
									<div class="title {{# if(!d[i].delivery){ }}pick{{# } }}" style="margin-bottom:0px;">
										<span class="star">
											{{#
												var tmpScore = parseFloat(d[i].star);
												if(tmpScore>0){
													for(var tmpI=0;tmpI<5;tmpI++){ if(tmpScore >= tmpI+1){ }}<i class="full"></i>{{# }else if(tmpScore > tmpI){ }}<i class="half"></i>{{# }else{ }}<i></i>{{# } }
												}else{
											}}
												<i class="full"></i><i class="full"></i><i class="full"></i><i class="half"></i><i></i>
											{{#
												}
											}}
										</span>
										{{# if(d[i].month_sale_count>0){ }}<span>月售{{ d[i].month_sale_count }}单</span>{{# } }}
										{{# if(d[i].delivery){ }}
											<em class="location-right">{{ d[i].delivery_time }} {{ d[i].delivery_time_type }}</em>
										{{# }else{ }}
											<em class="location-right ziti">门店自提</em>
										{{# } }}
									</div>
									{{# if(d[i].delivery){ }}
										<div class="price">
											<span>起送价 {pigcms{$config['Currency_symbol']}{{ d[i].delivery_price }}</span><span class="delivery">{{# if(d[i].delivery_money==0){ }}免配送费 {{# }else{ }} 配送费 {pigcms{$config['Currency_symbol']}{{ d[i].delivery_money }} {{# } }}</span>
											{{# if(d[i].delivery_system){ }}
												<em class="location-right">{{ deliverName }}</em>
											{{# }else{ }}
												<em class="location-right merchant_send" >商家配送</em>
											{{# } }}
										</div>
									{{# } }}

								</div>
								{{# if(d[i].coupon_count > 0){ }}
										<div class="coupon {{# if(d[i].coupon_count > 2){ }}hasMore{{# } }}">
											<ul>
												{{# var tmpCouponList = parseCoupon(d[i].coupon_list,'array');  }}
												{{# if(tmpCouponList['invoice']){ }}
													<li><em class="merchant_invoice"></em>{{ tmpCouponList['invoice'] }}</li>
												{{# } }}
												{{# if(tmpCouponList['discount']){ }}
													<li><em class="merchant_discount"></em>{{ tmpCouponList['discount'] }}</li>
												{{# } }}
												{{# if(tmpCouponList['minus']){ }}
													<li><em class="merchant_minus"></em>{{ tmpCouponList['minus'] }}</li>
												{{# } }}
												{{# if(tmpCouponList['newuser']){ }}
													<li><em class="newuser"></em>{{ tmpCouponList['newuser'] }}</li>
												{{# } }}
												{{# if(tmpCouponList['delivery']){ }}
													<li><em class="delivery"></em>{{ tmpCouponList['delivery'] }}</li>
												{{# } }}
												{{# if(tmpCouponList['system_minus']){ }}
												<li><em class="system_minus"></em>{{ tmpCouponList['system_minus'] }}</li>
												{{# } }}
												{{# if(tmpCouponList['system_newuser']){ }}
													<li><em class="system_newuser"></em>{{ tmpCouponList['system_newuser'] }}</li>
												{{# } }}
							                    {{# if(tmpCouponList['isDiscountGoods']){ }}
								                    <li><em class="isDiscountGoods"></em>{{ tmpCouponList['isDiscountGoods'] }}</li>
							                    {{# } }}
							                    {{# if(tmpCouponList['isdiscountsort']){ }}
								                    <li><em class="merchant_discount"></em>{{ tmpCouponList['isdiscountsort'] }}</li>
							                    {{# } }}
											</ul>
											{{# if(d[i].coupon_count > 2){ }}
												<div class="more">{{ d[i].coupon_count }}个活动</div>
											{{# } }}
										</div>
									{{# } }}
							</dd>
						{{# } }}
					<elseif condition="$config.guess_content_type eq 'meal'"/>
						{{# for(var i = 0, len = d.store_list.length; i < len; i++){ }}
							{{# if(d.store_list[i].state == 0){ }}
							<dl class="on">
								<dt>
									<a class='img_box' href="{{ d.store_list[i].url }}"><img src="{{ d.store_list[i].image }}"></a>
									<div class="navLtop clr recommend-link-url" data-url="{{ d.store_list[i].url }}">
										{{# if(d.store_list[i].isverify == 1){ }}
											<img src="./static/images/rec_2.png" style="width:18px; height:20px;margin-top:1px; margin-right:5px;float:left">
										{{# } }}
										<h2 class="fl1">{{ d.store_list[i].name }}</h2>
										<div class="navLtop_right fr">
											{{# if(d.store_list[i].is_book == 1){ }}
											<span class="ln">订</span>
											{{# } }}
											{{# if(d.store_list[i].is_queue == 1){ }}
											<span class="zi">排</span>
											{{# } }}
											{{# if(d.store_list[i].is_takeout == 1){ }}
											<span class="lv">外</span>
											{{# } }}
										</div>
									</div>
									<div class="navLBt clr">
										<ul class="navLBt_ul fl show_number clr">
											<li>
                                                <div>
                                                    <!--							<p tip="{{ d.store_list[i].score_mean }}" ></p>-->
                                                    <span class="star">
                                                        {{#
                                                            var tmpScore = parseFloat(d.store_list[i].score_mean);
                                                            if(tmpScore>0){
                                                                for(var tmpI=0;tmpI<5;tmpI++){ if(tmpScore >= tmpI+1){ }}<i class="full"></i>{{# }else if(tmpScore > tmpI){ }}<i class="half"></i>{{# }else{ }}<i></i>{{# } }
                                                            }else{
                                                        }}
                                                            <i class="full"></i><i class="full"></i><i class="full"></i><i class="half"></i><i></i>
                                                        {{#
                                                            }
                                                        }}
                                                    </span>
                                                </div>
											</li>
										</ul>
                                        {{# if (d.store_list[i].mer_discount > 0) { }}
                                        <div class=""><span style="border: 1px solid #a01615;color: #a01615;font-size: 12px;margin-left:10px;float:left;">{{ d.store_list[i].mer_discount }}折优惠</span></div>
                                        {{# } }}
										<div class="Notopen fl">未营业</div>
										<div class="distance fr">{{ d.store_list[i].range }}</div>
									</div>
								</dt>
							</dl>
							{{# } else { }}
							<dl>
								<dt>
									<a class='img_box' href="{{ d.store_list[i].url }}"><img src="{{ d.store_list[i].image }}"></a>
									<div class="navLtop clr recommend-link-url" data-url="{{ d.store_list[i].url }}">
										{{# if(d.store_list[i].isverify == 1){ }}
											<img src="./static/images/rec_2.png" style="width:18px; height:20px;margin-top:1px; margin-right:5px;float:left">
										{{# } }}
										<h2 class="fl1">{{ d.store_list[i].name }}</h2>
										<div class="navLtop_right fr">
											{{# if(d.store_list[i].is_book == 1){ }}
											<span class="ln">订</span>
											{{# } }}
											{{# if(d.store_list[i].is_queue == 1){ }}
											<span class="zi">排</span>
											{{# } }}
											{{# if(d.store_list[i].is_takeout == 1){ }}
											<span class="lv">外</span>
											{{# } }}
										</div>
									</div>
									<div class="navLBt clr">
										<ul class="navLBt_ul fl show_number clr">
											<li>
                                                <div>
                                                    <!--							<p tip="{{ d.store_list[i].score_mean }}" ></p>-->
                                                    <span class="star">
                                                        {{#
                                                            var tmpScore = parseFloat(d.store_list[i].score_mean);
                                                            if(tmpScore>0){
                                                                for(var tmpI=0;tmpI<5;tmpI++){ if(tmpScore >= tmpI+1){ }}<i class="full"></i>{{# }else if(tmpScore > tmpI){ }}<i class="half"></i>{{# }else{ }}<i></i>{{# } }
                                                            }else{
                                                        }}
                                                            <i class="full"></i><i class="full"></i><i class="full"></i><i class="half"></i><i></i>
                                                        {{#
                                                            }
                                                        }}
                                                    </span>
                                                </div>
											</li>
										</ul>
                                        {{# if (d.store_list[i].mer_discount > 0) { }}
                                        <div class=""><span style="border: 1px solid #a01615;color: #a01615;font-size: 12px;margin-left:10px;float:left;">{{ d.store_list[i].mer_discount }}折优惠</span></div>
                                        {{# } }}
										<div class="distance fr">{{ d.store_list[i].range }}</div>
									</div>
								</dt>
								{{# if(d.store_list[i].pay_in_store == 1){ }}
								<dd class="navlink clr">
									<a href="{{ d.store_list[i].store_pay }}">
										<span class="link_Pay">到店付</span>
                                        {{# if(d.store_list[i].discount_txt != ''){ }}
										{{# if(d.store_list[i].discount_txt.discount_type == 1){ }}
											<span>{{ d.store_list[i].discount_txt.discount_percent }}折</span>
										{{# } else { }}
											<span>每满{{ d.store_list[i].discount_txt.condition_price }}减{{ d.store_list[i].discount_txt.minus_price }}{pigcms{$config['Currency_txt']}</span>
										{{# } }}
										{{# } }}
										<span class="link_jt fr"></span>
									</a>
								</dd>
								{{# } }}
								{{# for(var j = 0, jlen = d.store_list[i].group_list.length; j < jlen; j++){ }}
								<dd class="Menulink clr">
									<a href="{{ d.store_list[i].group_list[j].url }}">
										<div class="Menulink_img fl">
											<img class="on" src="{{ d.store_list[i].group_list[j].list_pic }}">
											<span class="MenuGroup"></span>
										</div>
										<div class="Menulink_right">
											<h2>{{ d.store_list[i].group_list[j].name }}</h2>
											<div class="MenuPrice">
												<span class="PriceF"><i>{pigcms{$config['Currency_symbol']}</i><em>{{ d.store_list[i].group_list[j].price }}</em></span>
												<span class="PriceT">门市价:{pigcms{$config['Currency_symbol']}{{ d.store_list[i].group_list[j].old_price }}</span>
												<span class="PriceS">{{ d.store_list[i].group_list[j].sale_txt }}</span>
											</div>
										</div>
									</a>
								</dd>
								{{# } }}
							</dl>
							{{# } }}
						{{# } }}
                        <elseif condition="$config.guess_content_type eq 'store'"/>
                        {{# for(var i = 0, len = d.store_list.length; i < len; i++){ }}
						<dd class="link-url" data-url="{{ d.store_list[i].url }}">
							<div class="dealcard-img imgbox">
								{{# if(d.store_list[i].isverify == 1){ }}
									<img src="./static/images/kd_rec.png" style="width:41px;height:15px;position: absolute;z-index: 15;margin:2px 0 0 0">
								{{# } }}
								<img src="{pigcms{$config.site_url}/index.php?c=Image&a=thumb&width=276&height=168&url={{ encodeURIComponent(d.store_list[i].list_pic) }}" alt="{{ d.store_list[i].store_name }}"/>
							</div>
							<div class="dealcard-block-right" style="font-family:'Microsoft YaHei' !important;">
								<div class="brand" style="padding:0px 8px 0px 1px;font-weight:bolder;float:left;">{{ d.store_list[i].store_name }}</div>
								<div class="brand" style="float:right;">
									{{# if(d.store_list[i].have_shop == 1){ }}
										<i class="text-icon order-jiudian order-icon" style="background-color:#ea0d2c;width:20px;height:22px;font-size:14px;">{pigcms{$config.shop_alias_name}</i>
									{{# } }}
									{{# if(d.store_list[i].have_group == 1){ }}
										<i class="text-icon order-jiudian order-icon" style="background-color:#EAAD0D;width:20px;height:22px;font-size:14px;">{pigcms{$config.group_alias_name}</i>
									{{# } }}
									{{# if(d.store_list[i].have_meal == 1){ }}
										<i class="text-icon order-jiudian order-icon" style="width:20px;height:22px;font-size:14px;">{pigcms{$config.meal_alias_name}</i>
									{{# } }}
									{{# if(d.store_list[i].now_appoint){ }}
										<i class="text-icon order-jiudian order-icon" style="background-color:#0092DE;width:20px;height:22px;font-size:14px;">{pigcms{$config.appoint_alias_name}</i>
									{{# } }}
								</div>
								<div style="clear:both"></div>
								<div class="price" style="margin-bottom:5px;font-size:14px;">商家有<span style="color: #fe5842;font-size: 18px;">{{ d.store_list[i].fans_count }}</span>个粉丝</div>
								<div class="rateInfo" style="margin-top:3px;float:left;">
								{{# if(d.store_list[i].pingjun){ }}
									<div class="starIconBg">
										<div class="starIcon" style="width:{{ d.store_list[i].xing }}%"></div>
									</div>
									<div class="starText">{{ d.store_list[i].pingjun }}</div>
								{{# }else{ }}
									<span style="color:#999">暂无评分</span>
								{{# } }}
								</div>
								<div class="rateInfo" style="float:right;margin-top:4px;">
									<span style="color:#909090;float:right;font-size:12px">{{ d.store_list[i].range_txt }}</span>
								</div>
							</div>
						</dd>
		{{# if(d.store_list[i].pay_in_store == 1 && d.store_list[i].discount_txt != ''){ }}
		<dd class="navlink clr">
			<a href="{{ d.store_list[i].store_pay }}">
				<span class="link_Pay">到店付</span>
				{{# if(d.store_list[i].discount_txt.discount_type == 1){ }}
					<span>{{ d.store_list[i].discount_txt.discount_percent }}折</span>
				{{# } else { }}
					<span>每满{{ d.store_list[i].discount_txt.condition_price }}减{{ d.store_list[i].discount_txt.minus_price }}{pigcms{$config['Currency_txt']}</span>
				{{# } }}
				<span class="link_jt fr"></span>
			</a>
		</dd>
		{{# } }}
					{{# } }}
					</if>
				</script>

            <script id="indexMallTpl" type="text/html">
            {{# for (var i in d) { }}
            <div class="hrash">
                <div class="left_scroll" id="cat_{{ d[i][0].fid }}">
                    {{# console.log(i); }}
                    <ul class="clear">
                        {{# for (var ii in d[i]) { }}
                        {{# if (ii == 0) { }}
                            {{# var cateId = d[i][ii].id; }}
                            {{# mallGoods(cateId, true); }}
                            <li class="ft active" data-id="{{  d[i][ii].id }}">{{ d[i][ii].name }}</li>
                        {{# } else { }}
                            <li class="ft" data-id="{{ d[i][ii].id }}">{{ d[i][ii].name }}</li>
                        {{# } }}
                        {{# } }}
                    </ul>
                </div>
                <!--p class="moreClass">更多</p-->
            </div>
            <div class="picadds" >
                <div class="picContent" id="mall_{{ d[i][0].fid }}">
                    <ul class="clear">
                    </ul>
                </div>
            </div>
            {{# } }}
            </script>
            <script id="indexMallGoodsTpl" type="text/html">
            {{# for (var i in d) { }}
            <li class="ft">
                <a href="{{ d[i].url }}">
                <dl>
                    <dt><img src="{{ d[i].image }}"/></dt>
                    <dd class="sizes">{{ d[i].name }}</dd>
                    <dd class="high"><i></i>好评率{{ d[i].score_mean }}</dd>
                    <dd class="flexss"><span><span>{pigcms{$config['Currency_symbol']}</span>{{ d[i].price }}</span>
                    	{{# if(d[i].sell_count>0){ }}
                    		<p>{{ d[i].sell_count }}件已售</p>
                    	{{# }else if(d[i].is_new==1){ }}
                    		<p>新品上线</p>
                    	{{# } }}
                    </dd>
                </dl>
                </a>
            </li>
            {{# } }}
            </script>
				<div id="moress" style="text-align:center;padding:10px;">正在加载...</div>
				<div id="enddate" style="text-align:center;padding:10px;display:none;">没有更多数据了</div>
				<div id="pullUp" style="bottom:-60px;">
					<img src="{pigcms{$config.site_logo}" style="width:130px;height:40px;margin-top:10px"/>
				</div>

			</div>
		</div>



		<include file="Public:footer"/>
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
        <script type="text/javascript">
            var leftWidth=$('.left_scroll ul li').length;
            var picWidth=$('.picContent ul li').length;
            $('.picContent ul').width(picWidth*160+10);
            $('.left_scroll ul').width(leftWidth*81+60);
            $('.left_scroll ul li').click(function(e){
                $(this).addClass('active').siblings('li').removeClass('active');
                var distance=$(this).offset().left;
                $('.left_scroll').animate({"scrollLeft":distance},300);
            });
            $('.moreClass').click(function(e){
                $('.bg_mask').show();
                var text=$('.left_scroll li.active').text();
                $.each($('.bg_mask ul li'), function(i,val) {
                    if(text==$(this).text()){
                        $(this).addClass('active').siblings('li').removeClass('active');
                    }
                });
                $('.bg_mask .close').click(function(e){
                    $('.bg_mask').hide();
                });


                $('.bg_mask ul li').click(function(e){
                    var this_text=$(this).text();
                    $(this).addClass('active').siblings('li').removeClass('active');
                    $.each($('.left_scroll ul li'), function() {
                        if($(this).text()==this_text){
                            var distance=$(this).offset().left;
                            $('.left_scroll').animate({"scrollLeft":distance},300);
                            $(this).addClass('active').siblings('li').removeClass('active');
                        }
                    });
                    $('.bg_mask').hide();
                });
            });

        </script>
		{pigcms{$shareScript}
		{pigcms{$coupon_html}
	</body>
</html>