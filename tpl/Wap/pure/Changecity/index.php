<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>选择城市</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?210"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/changecity.css?210"/>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444" charset="utf-8"></script>
		<!-- <script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script> -->
		<script type="text/javascript" src="{pigcms{$static_public}js/jquery.cookie.js?210" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
		<script type="text/javascript">
			var indexUrl="<if condition="$url">{pigcms{$url}<else />{pigcms{:U('Home/index')}</if>";
			var cityTopDomain=".{pigcms{$config.many_city_top_domain}";
			var get_wxapp="{pigcms{:intval($_GET['wxapp'])}";
			var ajaxGetCitysByProUrl="{pigcms{:U('ajaxGetCitysByPro')}";
			var ajaxGetTradingAreaByProUrl="{pigcms{:U('ajaxGetTradingAreaByPro')}";
			var ajax_get_area_url="{pigcms{:U('ajax_get_area')}";
			var only_city="{pigcms{$_GET['only_city']}";
			var location_mode={pigcms{$config['location_mode']|intval=###};
		</script>
		<script type="text/javascript" src="{pigcms{$static_path}js/SelectChar.js?210" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/changecity.js?201811222" charset="utf-8"></script>
		<style>
			.nowCity{display:flex;align-items: center;padding:0px 50px 0px 12px;height: 37px;
				line-height: 37px;}
			.nowCity div{color:#7E7E7E;position:relative;background: #fff}
			#nowCity .hotKey li a , .moreCity .hotKey li a{
				border:0.5px solid #e4e1e1 ;
				padding: 8px 3px;
				display: block;
				text-align: center;
				font-size: 12px;
				color: #7E7E7E;
				border-radius: 3px;
			}
			.moreCity .hotKey ,#nowCity .hotKey{
				margin: 0px 17px 0px  1.6%;
				overflow: hidden;

			}
			#nowCity .hotKey li , .moreCity .hotKey li{
				width: 31%;
				float: left;
				margin-right: 1.6%;
				background-color: white;
				margin-bottom: 1.6%;
			}

			.giveContent:after{
				content: "";width: 6px;height: 6px;display: inline-block;
				border:2px solid #ddd;;
				border-right: 2px solid transparent;border-bottom:  2px solid transparent;
				position: absolute;top: 18px;
				-webkit-transform: rotate(45deg);
				transform: rotate(45deg);
				right:-19px;z-index:10
			}
			.giveContent_down:after{
				-webkit-transform: rotate(-135deg) !important;
				transform: rotate(-135deg) !important;
				top: 11px !important;
			}
			.city_location{position:relative}

			.bg_active{background-color: rgba(245,245,245,0.9);}
			.city_location_down:after{
				right:34px !important;
				top:10px !important;
				-webkit-transform: rotate(-135deg);
				transform: rotate(-135deg) ;
			}
			.rt:after{
				right:34px !important;
				top:18px !important;
				-webkit-transform: rotate(45deg) !important;
				transform: rotate(45deg) !important;

			}
			#selectCharBox,#seleMask,#smallChar{width:30px !important;}
		</style>
	</head>
	<body> 
		<header <if condition='$config["mobile_head_color"]'>style="background-color: {pigcms{$config["mobile_head_color"]};"</if><if condition="$config['many_city']">class="hasManyCity"</if>>当前城市-{pigcms{$now_select_city.area_name}<if condition="$now_select_area && $_GET['only_city'] neq 1">-{pigcms{$now_select_area.area_name}<elseif condition="$_GET['only_city'] neq 1"/>-全城</if></header>
		<div id="container">
			<div id="scroller">
				<if condition="$now_select_city['child'] && $_GET['only_city'] neq 1">
					<section class="hotBox"  id="nowCity">
						<div class="title">当前定位</div>
						<div style="background:#fff;padding-bottom: 5px;">
							<div class="nowCity" >
								<div style="flex:1">{pigcms{$now_select_city.area_name}<if condition="$now_select_area">-{pigcms{$now_select_area.area_name}<else/>-全城</if></div>
								<if condition="$_GET['only_city'] eq 1">
									<div style="text-align:right;" class="giveContent">选择城市</div>
								<else/>
									<div style="text-align:right;" class="giveContent">选择{pigcms{$type_name}</div>
								</if>
							</div>
							<ul class="hotKey" data-down="1">
								<li><a href="javascript:void(0)" data-city_name="{pigcms{$now_select_city.area_name}" data-city_id="{pigcms{$now_select_city.area_id}" <if condition="!$now_select_area">class="bg_active"</if>>全城</a></li>
								<volist name="now_select_city.child" id="child">
									<if condition="$_GET['only_city'] neq 1">
										<if condition="$config['location_mode']">
											<li><a href="javascript:void(0)" data-area_id="{pigcms{$child.market_id}" data-area_name="{pigcms{$child.market_name}" <if condition="$child['market_id'] eq $now_select_city['area_id']">class="bg_active"</if> data-city_id="{pigcms{$now_select_city.area_id}" data-city_name="{pigcms{$now_select_city.area_name}">{pigcms{$child.area_name}</a></li>
										<else/>
											<li><a href="javascript:void(0)" data-area_id="{pigcms{$child.area_id}" data-area_name="{pigcms{$child.area_name}" <if condition="$child['area_id'] eq $now_select_area['area_id']">class="bg_active"</if> data-city_id="{pigcms{$now_select_city.area_id}" data-city_name="{pigcms{$now_select_city.area_name}">{pigcms{$child.area_name}</a></li>
										</if>
									</if>
								</volist>
							</ul>
						</div>
					</section>
				</if>
				<section class="hotBox" id="historyCityList" style="display:none;" >
					<div class="title">最近访问的城市</div>
					<ul class="hotKeyUl">
					</ul>
				</section>
				<!-- <if condition="$hot_city">
					<section class="hotBox">
						<div class="title">热门城市</div>
						<ul class="hotKeyUl">
							<volist name="hot_city" id="vo">
								<li><a class="city_location" data-city_id="{pigcms{$vo.area_id}" data-city_name="{pigcms{$vo.area_name}">{pigcms{$vo.area_name}</a></li>
							</volist>
						</ul>
					</section>
				</if> -->
				<section class="citylistBox">
					<dl>
						<volist name="all_city" id="vo">
							<dt id="city_{pigcms{$key}" class="cityKey" data-city_key="{pigcms{$key}">{pigcms{$key}</dt>
							<volist name="vo" id="voo">
								<div class="moreCity" style="background:#fff;margin:0px;">
									<dd class="city_location giveContent <if condition='$_GET["only_city"] neq 1'>city_location_down</if>" data-city_id="{pigcms{$voo.area_id}"  data-city_name="{pigcms{$voo.area_name}">
										{pigcms{$voo.area_name}
									</dd>
								</div>
							</volist>
						</volist>
					</dl>
				</section>
			</div>
		</div>
		{pigcms{$hideScript}
		<div id="selectCharBox"></div>
	</body>
</html>