<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8" />
	<title>{pigcms{$config.employee_alias}列表</title>
	<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
	<meta name="apple-mobile-web-app-capable" content="yes"/>
	<meta name='apple-touch-fullscreen' content='yes'/>
	<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
	<meta name="format-detection" content="telephone=no"/>
	<meta name="format-detection" content="address=no"/>
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?210">
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/list.css?210"/>
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/index.css?1"/>
	<link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/icon.css"/>
	<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
	<script type="text/javascript">
		var open_extra_price =Number("{pigcms{$config.open_extra_price}");
		var extra_price_name ="{pigcms{$config.extra_price_alias_name}";
		var location_url = "{pigcms{:U('Employee/ajaxList', array('keyword' => $keyword))}";
		var now_cat_url="<if condition="!empty($now_cat_url)">{pigcms{$now_cat_url}<else/>-1</if>";
		var now_area_url="<if condition="!empty($now_area_url) && $all_area_list">{pigcms{$now_area_url}<else/>-1</if>";
		var now_sort_id="<if condition="!empty($now_sort_array) AND $config.open_default_sort eq 0 AND $config.open_group_default_sort eq 0">{pigcms{$now_sort_array.sort_id}<else/>defaults</if>";
		<if condition="$long_lat">var user_long = "{pigcms{$long_lat.long}",user_lat = "{pigcms{$long_lat.lat}";<else/>var user_long = '0',user_lat  = '0';</if>

		var change_url = "{pigcms{:U('Employee/employee_store_list',array('key'=>'store'))}";
		var store_url = "{pigcms{:U('Employee/employee_store_list',array('key'=>'store'))}";
		var employee_alias = "{pigcms{$config.employee_alias}";
		var slider_num = "{pigcms{$wap_index_slider_count}";
	</script>
	<script type="text/javascript" src="{pigcms{$static_path}employee/js/dropdown.js?t={pigcms{:time()}" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}employee/js/employee_list.js?t={pigcms{:time()}" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/idangerous.swiper.min.js" charset="utf-8"></script>
	<style>
		.btn-tip {
			border: 1px solid #f1f1f1;
			padding: 1px 3px;
			text-align: center;
			line-height: 1.5rem;
			border-radius: 0.3rem;
			float: left;
			margin-bottom: 10px;
			margin-right: 10px;
			min-width: 4.5rem;
		}
		.btn-tip.active {
			background-color: #06c1ae;
			color: white;
		}
		.all-tip {
			border: 1px solid #f1f1f1;
			padding: 1px 3px;
			text-align: center;
			line-height: 1.5rem;
			border-radius: 0.3rem;
			float: left;
			margin-top: 8px;
			margin-left: 42%;
			min-width: 4.5rem;
		}
		.all-tip.active {
			background-color: #06c1ae;
			color: white;
		}
		.tip-content {
			padding-left: 10px;
		}
		.clear-both-float {
			clear: both;
		}

		.cat-li:first-child {
			border-top: none !important;
			border-bottom: none !important;
		}
		.cat-li {
			border-top: 1px solid #f1f1f1;
			border-bottom: none !important;
		}
		.screen-wrapper {
			display: none;
		}
		.screen-wrapper.active {
			display: block;
		}
		.btn-chongzhi {
			float: left;
			width: 50%;
			text-align: center;
			border-right: 1px solid #f1f1f1;
		}
		.btn-finish{
			color: #06c1ae;
			text-align: center;
		}
		.btn-info{
			position: absolute;
			bottom: 0;
			width: 100%;
			background-color: white;
		}
		.title_change {
			height: 50px;
			border-bottom: 1px solid #c6c0b3;
			font-size: 18px;
			text-align: center;
		}
		.title_change > ul{
			height: 50px;
		}

		.title_change > ul>li{
			line-height: 47px;width: 50%;
			text-align: center;
		}

		.title_change > ul>.active{
			color: #06c1ae;
		}
		.title_change > ul>.active div{
			height: 47px;
			margin: 0 auto;
			color: #06c1ae;
		}
		.alias_txt{
			width: 125px;
			font-size: 18px;
			overflow: hidden;
			white-space: nowrap;
			text-overflow: ellipsis;
		}
		.title_change > ul>.active div> p{
			width: 30%;
			margin: 0 auto;
			border-bottom:3px solid #06c1ae;
			color: #06c1ae;
		}
		.title_boder {
			border-right: 2px solid #edebeb;
		}
		.slider {
			background-color: white;
			border-bottom: 1px solid #edebeb;
			margin-bottom: 0px;
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

		element.style {
			height: 168px;
			cursor: -webkit-grab;
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
		.swiper-container, .swiper-wrapper, .swiper-slide {
			width: 100%;
			height: 100%;
		}
		.dealcard dd {
			border-bottom: 2px solid #f1f1f1;
		}
		.icon-dbprice{
			-webkit-transform: translate(-50%, -50%);
			transform: translate(-50%, -50%);
			-webkit-transform-origin: 0 0;
			transform-origin: 0 0;
			position: absolute;
			font-size: 40px;
			top: 50%;
			left: 50%;
			color: #06c1ae;
		}
		.left_t:after {
			content: " ";
			position: absolute;
			left: 50%;
			top: 0;
			bottom: 70%;
			width: 2px;
			border-left: 2px solid #d5d5d6;
			color: #d5d5d6;
			/*-webkit-transform-origin: 0 0;
            transform-origin: 0 0;
            -webkit-transform: scaleX(.5);
            transform: scaleX(.5);*/
		}
		.left_b:after {
			content: " ";
			position: absolute;
			left: 50%;
			bottom: 0;
			top: 70%;
			width: 2px;
			border-left: 2px solid #d5d5d6;
			color: #d5d5d6;
			/*-webkit-transform-origin: 0 0;
            transform-origin: 0 0;
            -webkit-transform: scaleX(.5);
            transform: scaleX(.5);*/
		}
		.dealcard .brand {
			line-height: 16px;
		}

	</style>
</head>
<body>
<section class="searchBar pageSliderHide <if condition="!$is_weixin_browser">wap</if>">
<div class="searchBox">
	<form id="search-form" action="" method="">
		<input type="search" value="{pigcms{$keyword}" id="keyword" name="keyword" placeholder="搜索{pigcms{$config['employee_alias']}" autocomplete="off"/>
	</form>
</div>
<div class="voiceBtn"></div>
</section>
<if condition="$wap_index_slider">
	<section class="slider">
		<div class="swiper-container swiper-container2" <if condition="COUNT($wap_index_slider) gt 4">style="height:168px;"<else/>style="height:88px;"</if>>
		<div class="swiper-wrapper">
			<volist name="wap_index_slider" id="vo">
				<div class="swiper-slide">
					<ul class="icon-list num{pigcms{$wap_index_slider_number}">
						<volist name="vo" id="voo">

							<li class="icon">
								<a href="{pigcms{$voo.url}">
                                                            <span class="icon-circle">
                                                                <img src="{pigcms{$voo.pic}">
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
		<div style="background: #ddd; height: 10px;display:none" class="hd"></div>
	</section>
</if>
<section class="navBox title_change">
	<span class="iconfont comments icon-dbprice" style=" font-size: 36px;" onclick="change(this, 'store')"></span >
	<span class="left_t" ></span >
	<span class="left_b" ></span >
	<ul>
		<li class="active ">
			<div>
				<div class="alias_txt">{pigcms{$config['employee_alias']}</div>
				<p></p>
			</div>
		</li>
		<li onclick="change(this, 'store')">
			<div>
				<div class="alias_txt" style="width: 100%;">{pigcms{$config['merchant_alias_name']}</div>

			</div>
		</li>
	</ul>
</section>
<section class="navBox pageSliderHide">
	<ul>
		<li class="dropdown-toggle caret category" data-nav="category">
			<span class="nav-head-name"><if condition="$now_category">{pigcms{$now_category.cat_name}<else/>全部分类</if></span>
		</li>
		<li class="dropdown-toggle caret biz subway" data-nav="biz">
			<span class="nav-head-name"><if condition="$now_area">{pigcms{$now_area.area_name}<else/>全城</if></span>
		</li>
		<li class="dropdown-toggle caret sort" data-nav="sort">
			<span class="nav-head-name">{pigcms{$now_sort_array.sort_value}</span>
		</li>
	</ul>
	<div class="dropdown-wrapper">
		<div class="dropdown-module">
			<div class="scroller-wrapper">
				<div id="dropdown_scroller" class="dropdown-scroller" style="overflow:hidden;">
					<div>
						<ul>
							<li class="category-wrapper">
								<ul class="dropdown-list">
									<li data-category-id="-1" <if condition="empty($top_category)">class="active"</if> onclick="list_location($(this));return false;"><span data-name="全部分类">全部分类</span></li>
									<volist name="all_category_list" id="vo">
										<li data-category-id="{pigcms{$vo.cat_url}" data-dir="1" <if condition="$vo['cat_count'] gt 0">data-has-sub="true"<else/>onclick="list_location($(this));return false;"</if> class="<if condition="$vo['cat_count'] gt 0">right-arrow-point-right</if> <if condition="$top_category['cat_url'] eq $vo['cat_url']">active</if>">
										<span data-name="{pigcms{$vo.cat_name}">{pigcms{$vo.cat_name}</span>
										<if condition="$vo['cat_count'] gt 0"><span class="quantity"><b></b></span></if>
										<div class="sub_cat hide" style="display:none;">
											<if condition="$vo['cat_count'] gt 0">

												<ul class="dropdown-list">
													<li data-category-id="{pigcms{$vo.cat_url}" onclick="list_location($(this));return false;"><div><span class="sub-name" data-name="{pigcms{$vo.cat_name}">全部</span></div></li>
													<volist name="vo['category_list']" id="second">
														<li  data-category-id="{pigcms{$second.cat_url}" data-dir="2" <if condition="$second['cat_count'] gt 0">data-has-sub="true"<else/>onclick="list_location($(this));return false;"</if> class="second_sub <if condition="$second['cat_count'] gt 0">right-arrow-point-right</if>">
														<span data-name="{pigcms{$second.cat_name}">{pigcms{$second.cat_name}</span>
														<if condition="$second['cat_count'] gt 0"><span class="quantity"><b></b></span></if>
														<div class="sub_cat hide" style="display:none;">
															<if condition="$second['cat_count'] gt 0">
																<ul class="dropdown-list sub-list">
																	<li data-category-id="{pigcms{$second.cat_url}" onclick="list_location($(this));return false;"><div><span class="sub-name" data-name="{pigcms{$second.cat_name}">全部</span></div></li>
																	<volist name="second['category_list']" id="third">
																		<li data-category-id="{pigcms{$third.cat_url}" onclick="list_location($(this));return false;"><div><span class="sub-name" data-name="{pigcms{$third.cat_name}">{pigcms{$third.cat_name}</span></div></li>
																	</volist>
																</ul>
															</if>
														</div>
														</li>
													</volist>
												</ul>


											</if>
										</div>
										</li>
									</volist>
								</ul>
							</li>
							<if condition="$all_area_list">
								<li class="biz-wrapper">
									<ul class="dropdown-list">
										<li data-area-id="-1" <if condition="empty($now_area_url)">class="active"</if> onclick="list_location($(this));return false;"><span data-name="全城">全城</span></li>
										<volist name="all_area_list" id="vo">
											<li data-area-id="{pigcms{$vo.area_url}" <if condition="$vo['area_count'] gt 0">data-has-sub="true"<else/>onclick="list_location($(this));return false;"</if> class="<if condition="$vo['area_count'] gt 0">right-arrow-point-right</if> <if condition="$top_area['area_url'] eq $vo['area_url']">active</if>">
											<span>{pigcms{$vo.area_name}</span>
											<if condition="$vo['area_count'] gt 0"><span class="quantity"><b></b></span></if>
											<div class="sub_cat hide" style="display:none;">
												<if condition="$vo['area_count'] gt 0">
													<ul class="dropdown-list sub-list">
														<li data-area-id="{pigcms{$vo.area_url}" onclick="list_location($(this));return false;"><div><span class="sub-name" data-name="{pigcms{$vo.area_name}">全部</span></div></li>
														<volist name="vo['area_list']" id="voo" key="j">
															<li data-area-id="{pigcms{$voo.area_url}" onclick="list_location($(this));return false;"><div><span class="sub-name" data-name="{pigcms{$voo.area_name}">{pigcms{$voo.area_name}</span></div></li>
														</volist>
													</ul>
												</if>
											</div>
											</li>
										</volist>
									</ul>
								</li>
							</if>
							<li class="sort-wrapper">
								<ul class="dropdown-list">
									<volist name="sort_array" id="vo">
										<li data-sort-id="{pigcms{$vo.sort_id}" <if condition="$vo['sort_id'] eq $now_sort_array['sort_id']">class="active"</if> onclick="list_location($(this));return false;"><span data-name="{pigcms{$vo.sort_value}">{pigcms{$vo.sort_value}</span></li>
									</volist>
								</ul>
							</li>
						</ul>
					</div>
				</div>
				<div id="dropdown_sub_scroller" class="dropdown-sub-scroller clear-both-float"><div></div></div>
				<div id="dropdown_sub_scroller_second" class="dropdown-sub-scroller clear-both-float" style="background: white;"><div></div></div>
				<div id="dropdown_sub_scroller_third" class="dropdown-sub-scroller clear-both-float" style="background: white;"><div></div></div>
			</div>
		</div>
	</div>
</section>
<if condition="!$home_menu_list">
	<php>$no_footer = true;</php>
</if>
<include file="Public:footer"/>
<style>
	.pin_style{
		background-repeat: no-repeat;
		background-image:url({pigcms{$static_path}images/pin.png);
		width:100%;
		height:100%;
		position: absolute;
	}
	.line_m{text-decoration:line-through;display: inline;color: #ccc}
	.s_name{
		float: left;
		width: 25%;
		overflow: hidden;
		text-overflow:ellipsis;
		white-space: nowrap;
	}
	.brand .txt_infos {
		float: left;
		overflow: hidden;
		text-overflow:ellipsis;
		white-space: nowrap;
		color: #06c1ae;
		font-size: 12px;
		border: 1px solid #06c1ae;
		border-radius: 5px;
		padding: 0px 2%;
		text-align: center;
		line-height: normal;
		max-width: 30%;
		width:auto;
	}
	.txt_info {
		float: left;
		width: 30%;
		overflow: hidden;
		text-overflow:ellipsis;
		white-space: nowrap;
	}
	.txt_len_info {
		float: left;
		width: 23%;
		overflow: hidden;
		text-overflow:ellipsis;
		white-space: nowrap;
	}

	.clear_both_info {
		clear: both;
	}

	.discount{
		border: 1px solid #eda4a4;
		color: #cc0000;
		background-color: #fae6e6 !important;
	}
	.tag{
		border: 1px solid #fbd3a4;
		color: #f8a100;
		background-color:#fef3e6;
	}
	.goodList li .tip {
		margin-bottom: 2px;
	}
	.line-tip {
		color: #06c1ae;
		border: #06c1ae 1px solid;
		display: inline-block;
		border-radius: 5px;
		padding: 1px 3px;
		margin-top: 5px;
		font-size: 10px;
	}
	.goodList li .title {
		height: 16px;
		line-height: 16px;
	}
	.goodList li .tip .tip-info {
		font-size: 10px;
		max-height: 56px;
		overflow: hidden;
	}
	.juli_txt {
		position: absolute;
		bottom: 5px;
		right: 4px;
		color: #999;
		font-size: 12px;
		z-index: 99999;
	}
	.juli_txt_navigation {
		position: absolute;
		bottom: 30px;
		right: 4px;
		color: #999;
		font-size: 12px;
		z-index: 99999;
	}
	.cursor {
		cursor:pointer;
	}

	.txt_len_info{padding-left:3%}
	.price .txt_len_info{padding-left:0%}

	.dd{
		clear: both;

		width: 100%;
		overflow: hidden;
	}

	.d_name{
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		float: left;
		display: block;
		width: 21%;
	}
	.d_content{
		float: left;
		display: block;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		color: #06c1ae;
		font-size: 12px;
		border: 1px solid #06c1ae;
		border-radius: 5px;
		padding: 0px 2%;
		text-align: center;
		width: 21%;
	}
	.all_p{
		padding-left: 10px;
		float: right;
		font-size: 12px;
	}
</style>
<div id="container">
	<div id="scroller">
		<div id="pullDown">
			<span class="pullDownIcon"></span><span class="pullDownLabel">下拉可以刷新</span>
		</div>
		<script id="ListBoxTpl" type="text/html">
			{{# for(var i = 0, len = d.list.length; i < len; i++){ }}
			<dd style="display:flex;align-items: center" class="link-url cursor" data-url="{{ d.list[i].url }}">
				<div style="    width: 80px;   height: 80px; border-radius: 2px;">
					<img style="width:100%;height:100%" src="{pigcms{$config.site_url}/index.php?c=Image&a=thumb&width=276&height=168&url={{ encodeURIComponent(d.list[i].avatar) }}" alt="{{ d.list[i].true_name }}"/>
				</div>
				<div  style="flex:1;padding-left:10px;min-width:0px">
					<div class="dd">
						<span class="d_name">{{#if(d.list[i].true_name){}}{{ d.list[i].true_name }}{{# } else { }}{{ d.list[i].nickname }}{{# } }}</span>
						{{# if(d.list[i].cat_name){ }} <span class="d_content">{{ d.list[i].cat_name }}</span>{{# } }}

						{{# if(d.list[i].score_average > 0){ }}
						<span class="all_p" style="font-size:12px">综评<span style="color:#ff9c00">{{ d.list[i].score_average  }}</span>分</span>
						{{# }else{ }}
						<span class="all_p" style="font-size:12px">暂无评分</span>
						{{# } }}
						{{# if(d.list[i].thumb_num > 0){ }}
						<span class="" style="font-size:12px;float:right">点赞<span style="color:#ff9c00">{{ d.list[i].thumb_num }}</span>人</span>
						{{# } }}

					</div>
					<div class="" style="width: 88%;overflow: hidden; text-overflow: ellipsis;  white-space: nowrap;">{{ d.list[i].merchant_store_name }}</div>
					<div style="display:flex;align-items: center">
						<div style="width:70%">

							<div class="price">
								{{# if(d.list[i].circle_name){ }}<div class="txt_info">{{ d.list[i].circle_name }}</div>{{# } }}
								{{# if(d.list[i].address_info){ }}<div class="txt_len_info" style="   width: 65%;">{{ d.list[i].address_info }}</div>{{# } }}
							</div>
						</div>
						<div style="text-align:center;flex:1" class="navi cursor" data-lng="{{ d.list[i].long }}" data-lat="{{ d.list[i].lat }}" data-name="{{ d.list[i].address_info }}" data-address="{{ d.list[i].address_info }}">

							<em style="display:block">
								<img src="{pigcms{$static_path}images/dindxqt_11.png" width="18" height="20" style="position: relative; top: 5px;">
							</em>
							<span class="cursor " style="    color: #999;">{{ d.list[i].juli_txt }}</span>

						</div>
					</div>

				</div>
			</dd>

			{{# } }}
		</script>
		<section class="listBox">
			<div style="background: #ddd; height: 5px;"></div>
			<dl></dl>
			<div class="shade hide"></div>
			<div class="no-deals hide">暂无此类{pigcms{$config.employee_alias}，请查看其他分类</div>
		</section>
		<div id="pullUp">
			<span class="pullUpIcon"></span><span class="pullUpLabel">上拉加载更多</span>
		</div>
	</div>
</div>

<script type="text/javascript">
	$(function(){
		if($(".slider li").length>0){
			$(".hd").show()
		}


		// 语音事件 start
		$('.voiceBtn').click(function(e){
			$('body').append('<div class="voiceContainer"><div class="close"></div><div class="title">请大声说出您想要找什么</div><div class="content"><div class="tip">点击按钮开始录音</div><div class="btn"></div></div><div class="searchBtm1"></div><div class="searchBtm2"></div></div>');
			$('.searchBtm1').css({'background-size':$('.searchBtm1').width()+'px '+$('.searchBtm1').height()+'px'});
			$('.searchBtm2').css({'background-size':$('.searchBtm2').width()+'px '+$('.searchBtm2').height()+'px'});

			$('.voiceContainer .close').click(function(){
				if(voiceBtmTimer){
					clearInterval(voiceBtmTimer);
				}
				$('.voiceContainer').remove();
				if($('.voiceContainer .btn').hasClass('start')){
					endVoice();
				}
			});
			$('.voiceContainer .btn').click(function(){
				if($(this).hasClass('start')){
					$('.voiceContainer .tip').html('点击按钮开始录音');
					$(this).removeClass('start');
					clearInterval(voiceBtmTimer);
					endVoice();
				}else{
					beginVoice();
				}
			});
			return false;
		});
		// 微信开始录音
		function beginVoice(){
			wx.startRecord({
				success: function (res){
					$('.voiceContainer .tip').html('点击按钮结束录音');
					$('.voiceContainer .btn').addClass('start');
					var voiceBtmNumber = 0;
					voiceBtmTimer = setInterval(function(){
						if(voiceBtmNumber%5 < 3){
							$('.searchBtm1').css({left:(parseInt($('.searchBtm1').css('left'))-1)+'px'});
						}else{
							$('.searchBtm2').css({left:(parseInt($('.searchBtm2').css('left'))-1)+'px'});
						}
						voiceBtmNumber += 1;
					},15);

				},
				fail: function (res){
					if(res.errMsg == 'startRecord:system permission denied'){
						motify.log('您拒绝了授权录音');
					}else{
						motify.log('未成功调用录音');
					}
				},
				cancel: function(){
					motify.log('您拒绝了授权录音');
				}
			});
		}
		// 微信结束录音
		function endVoice(){
			if(typeof(voice_type)=='undefined'){
				voice_type = 'group';
			}
			wx.stopRecord({
				success: function (res) {
					voiceLocalId = res.localId;
					// alert(voiceLocalId);
					/* wx.playVoice({
                        localId: voiceLocalId // 需要播放的音频的本地ID，由stopRecord接口获得
                    }); */
					wx.translateVoice({
						localId: voiceLocalId, // 需要识别的音频的本地Id，由录音相关接口获得
						isShowProgressTips: 1, // 默认为1，显示进度提示
						success: function (res) {
							if(res.translateResult){
								res.translateResult = res.translateResult.replace('。','');
								// motify.log('<div style="color:#06c1ae;">'+res.translateResult+'</div>正在为您查询...',0); // 语音识别的结果
								$('.voiceContainer').remove();

								// $('.voiceContainer').parentNode.removeChild($('.voiceContainer'));

								var search_txt = res.translateResult;
								$('#search-form #keyword').val(search_txt);
								location_url ='{pigcms{:U('Employee/ajaxList')}&keyword='+search_txt;
								now_page = 0;
								getList(false);
								// window.location.href = window.location.pathname+'?c=Search&a='+voice_type+'&w='+encodeURIComponent(res.translateResult);
							}else{
								motify.log('没有听出您说了什么，请重试');
							}
						},fail: function (res) {
							motify.log('无法识别该段语音，请重试');
						}
					});
				},fail: function (res) {

				}
			});
		}
		// 搜索触发事件
		$('#keyword').bind('input', function(e){
			var search_txt = $(this).val();
			location_url ='{pigcms{:U('Employee/ajaxList')}&keyword='+search_txt;
			now_page = 0;
			getList(false);
			$('.no-deals').css('display','none')
		});
		$('#search-form').submit(function(){
			return false;
		});
	})
	window.shareData = {
		"moduleName":"employee",
		"moduleID":"0",
		"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
		"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Employee/employee_list')}",
		"tTitle": "{pigcms{$config.employee_alias}列表",
		"tContent": "{pigcms{$config.site_name}"
	};
</script>
{pigcms{$shareScript}
{pigcms{$coupon_html}
</body>
</html>