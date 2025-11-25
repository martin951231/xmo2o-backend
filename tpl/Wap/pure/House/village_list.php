<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$config['house_name']}列表</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?210"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/village_list.css?211"/>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444" charset="utf-8"></script>
		<!-- <script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script> -->
		<script type="text/javascript" src="{pigcms{$static_public}js/jquery.cookie.js?210" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
		<script type="text/javascript">
			var location_url = "{pigcms{:U('House/ajax_village_list')}",keyword = "",backUrl="{pigcms{:U('Home/index')}",account = "{pigcms{$_GET.account}";
			<if condition="$long_lat">var user_long = "{pigcms{$long_lat.long}",user_lat = "{pigcms{$long_lat.lat}";<else/>var user_long = '0',user_lat  = '0';</if>
		</script>
		<script type="text/javascript" src="{pigcms{$static_path}js/village_list.js?210" charset="utf-8"></script>
		<style>
			#cityBtn{
				width: 60px;
			    height: 100%;
				line-height:50px;
				text-align:center;
				color:white;
				top: 0;
			    left: 0;
				font-size:14px;
				float:left;
			}
			#cityBtn:after {
			    content: "";
			    display: inline-block;
			    margin-left: 6px;
			    width: 8px;
			    height: 8px;
			    border: 1px solid white;
			    border-width: 0 1px 1px 0;
			    border-top-width: 0px;
			    border-right-width: 1px;
			    border-bottom-width: 1px;
			    border-left-width: 0px;
			    -webkit-transform: rotate(45deg);
			    margin-top: 18px;
			    vertical-align: top;
			}
			.flex{display:flex !important;display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; align-items: center;  }
			.flex_one{  flex:1;  -webkit-box-flex: 1;  -webkit-flex: 1;  }
		</style>
	</head>
	<body>
    <if condition="!$is_app_browser">
        <header class="pageSliderHide" <if condition='$config["mobile_head_color"]'>style="background-color: {pigcms{$config["mobile_head_color"]};"</if>>
		<if condition='!defined("IS_INDEP_HOUSE")'>
			<div id="backBtn"></div>{pigcms{$config['house_name']}列表
		<else />
		<div id="cityBtn" class="link-url" data-url="{pigcms{:U('Changecity/index')}">{pigcms{$config.now_select_city.area_name}</div>
			<div style=" float:left; text-align:center; width:80%">{pigcms{$config['house_name']}列表</div>
		</if>
		</header>
    </if>

		<section class="searchBar wap pageSliderHide">
			<div class="searchBox">
				<form id="search-form" method="post">
					<input type="search" id="keyword" name="w" placeholder="请输入{pigcms{$config['house_name']}名" autocomplete="off"/>
				</form>
			</div>
			<div class="voiceBtn"></div>
		</section>
        <div id="container">
			<div id="scroller">
				<div id="pullDown">
					<span class="pullDownIcon"></span><span class="pullDownLabel">下拉可以刷新</span>
				</div>
				<section class="villageBox hide" id="bindVillageBox">
					<div class="headBox">居住{pigcms{$config['house_name']}</div>
					<dl></dl>
				</section>
                
                <section class="villageBox hide" id="bindFamilyBox">
					<div class="headBox">绑定<if condition="$config['open_house_user_name']">亲友<else/>家属</if>{pigcms{$config['house_name']}</div>
					<dl></dl>
				</section>
                
				<section class="villageBox hide" id="villageBox">
					<div class="headBox">{pigcms{$config['house_name']}列表</div>
					<dl></dl>
				</section>
				<div class="noMoreDiv hide">未找到相关{pigcms{$config['house_name']}</div>
				<script id="villageBoxTpl" type="text/html">
					{{# for(var i = 0, len = d.length; i < len; i++){ }}
						<dd class="link-url flex"	{{# if(d[i].flag){ }} data-url="{pigcms{:U('House/village_select')}&village_id={{ d[i].village_id }}&pigcms_id={{ d[i].pigcms_id }}" {{# }else{ }}data-url="{pigcms{:U('House/village_select')}&village_id={{ d[i].village_id }}"{{# } }}>
							<div class="is_show" style="margin-right:10px;width:60px;height:60px;position: relative;">
                                <img src="{{ d[i].village_logo }}" style="width:100%;height:100%"/>
                                {{# if(d[i].isverify==1){ }}
								<span style=" background: rgb(244,203,124,0.99); position: absolute;  top: 0px; right: 0px;  font-size: 12px;padding: 0px 3px;  color: #fff;">已认证</span>
								{{# } }}
							</div>
							<div class="flex_one">
								<div class="brand">{{ d[i].village_name }}{{# if(d[i].flag){ }}<span class="location-right">业主：{{ d[i].name }}</span>{{# } }}{{# if(d[i].range){ }}<span class="location-right">{{ d[i].range }}</span>{{# } }}</div>
								<div class="title is_show">物业公司：{{ d[i].property_name }}</div>
								<div class="title is_show">联系电话：{{ d[i].property_phone }}</div>
								<div class="title">地址：{{ d[i].village_address }}</div>
							</div>

						</dd>
					{{# } }}
				</script>
				<div id="pullUp">
					<span class="pullUpIcon"></span><span class="pullUpLabel">上拉加载更多</span>
				</div>
			</div>
		</div>
		{pigcms{$hideScript}
	</body>
</html>