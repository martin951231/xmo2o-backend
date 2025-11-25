<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$config.meal_alias_name}搜索</title>
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
			var location_url="{pigcms{:U('Mealservice/search',array('w'=>urlencode($keywords)))}",now_sort="<if condition="!empty($now_sort)">{pigcms{$now_sort}<else/>defaults</if>";
			var voice_type = "meal";
			var main_color = "{pigcms{$config["mobile_head_color"]}";
		</script>
		<script type="text/javascript" src="{pigcms{$static_path}js/mealsearch.js?210" charset="utf-8"></script>
	</head>
	<body>
		<section class="searchBar pageSliderHide <if condition="!$is_weixin_browser">wap</if>" style="background-color:white;border-bottom:1px solid #edebeb;">
			<div class="searchBox">
				<form id="search-form" action="{pigcms{:U('Search/meal')}" method="post">
					<input type="search" id="keyword" name="w" placeholder="请输入搜索词" autocomplete="off" value="{pigcms{$keywords}"/>
				</form>
			</div>
			<div class="voiceBtn"></div>
		</section>
		<section class="searchBox pageSliderHide">
			<ul>
				<li class="dropdown-toggle link-url" data-url="{pigcms{:U('Search/shop',array('w'=>urlencode($keywords)))}" target="self"><span class="nav-head-name">{pigcms{$config.shop_alias_name}</span></li>
				<li class="dropdown-toggle link-url" data-url="{pigcms{:U('Search/group',array('w'=>urlencode($keywords)))}" target="self"><span class="nav-head-name">{pigcms{$config.group_alias_name}</span></li>
				<li class="dropdown-toggle active" data-url-type="openLeftWindow" style='color:{pigcms{$config["mobile_head_color"]};border-color:{pigcms{$config["mobile_head_color"]}'><span class="nav-head-name">{pigcms{$config.meal_alias_name}</span></li>
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
				<section class="navBox">
					<ul style="border-top: 1px solid #edebeb;">
						<li class="dropdown-toggle caret sort <if condition="$now_sort eq 'default'">active</if>" style="color:<if condition="$now_sort eq 'default'">{pigcms{$config["mobile_head_color"]}</if>" data-sort="default"><span class="nav-head-name">默认排序</span><i class="icon iconfont bottom">&#xe791;</i><i class="icon iconfont top">&#xe603;</i></li>
						<li class="dropdown-toggle caret sort <if condition="$now_sort eq 'hot'">active</if>" style="color:<if condition="$now_sort eq 'hot'">{pigcms{$config["mobile_head_color"]}</if>" data-sort="hot"><span class="nav-head-name">销量最高</span><i class="icon iconfont bottom">&#xe791;</i><i class="icon iconfont top">&#xe603;</i></li>
						<li class="dropdown-toggle caret sort <if condition="$now_sort eq 'price-asc'">active</if>" style="color:<if condition="$now_sort eq 'price-asc'">{pigcms{$config["mobile_head_color"]}</if>" data-sort="price-asc"><span class="nav-head-name">人均消费最低</span><i class="icon iconfont bottom">&#xe791;</i><i class="icon iconfont top">&#xe603;</i></li>
					</ul>
				</section>
				<script id="groupListBoxTpl" type="text/html">
					{{# for(var i = 0, len = d.group_list.length; i < len; i++){ }}
						<dd class="link-url" data-url="{{ d.group_list[i].url }}">
							{{# if(d.group_list[i].isverify == 1){ }}
									<img src="./static/images/kd_rec.png" style="width:41px;height:15px;position: absolute;z-index: 15;margin:2px 0 0 0"/>
								{{# } }}
							<div class="dealcard-img imgbox">
								<img src="{{ d.group_list[i].image }}" alt="{{ d.group_list[i].name }}" style="margin-left:0px;"/>
							</div>
							<div class="dealcard-block-right">
								<div class="brand">{{ d.group_list[i].name }}</div>
								<div class="title">{{ d.group_list[i].txt_info }}</div>
								<div class="price">
									{{# if(d.group_list[i].mean_money){ }}<strong>{{ d.group_list[i].mean_money }}</strong><span class="strong-color">{pigcms{$config['Currency_txt']}(人均)</span>{{# } }}&nbsp;<span class="line-right">已售{{ d.group_list[i].sale_count }}</span>
								</div>
							</div>
						</dd>
					{{# } }}
				</script>
				<section class="listBox">
					<dl class="dealcard">
						<volist name="group_list" id="vo">
							<dd class="link-url" data-url="{pigcms{$vo.url}">
								<if condition="$vo['isverify'] eq  1">
									<img src="./static/images/kd_rec.png" style="width:41px;height:15px;position: absolute;z-index: 15;margin:2px 0 0 0"/>
								</if>
								<div class="dealcard-img imgbox">
									<img src="{pigcms{$vo.image}" alt="{pigcms{$vo.name}" style="margin-left:0px;"/>
								</div>
								<div class="dealcard-block-right">
									<div class="brand">{pigcms{$vo.search_name}</div>
									<div class="title" style="padding-left:0px;">{pigcms{$vo.txt_info}</div>
									<div class="price">
										<if condition="$vo['mean_money']"><strong>{pigcms{$vo.mean_money}</strong><span class="strong-color">{pigcms{$config['Currency_txt']}(人均)</span></if>&nbsp;<span class="line-right">已售{pigcms{$vo.sale_count}</span>
									</div>
								</div>
							</dd>
						</volist>
					</dl>
					<div class="noMoreList <if condition="empty($group_list) || $totalPage gt 1">hide</if>">没有更多内容了!</div>
					<div class="shade hide"></div>
					<div class="no-deals <if condition="!empty($group_list)">hide</if>"><div><img src="{pigcms{$static_path}img/search.png"></div>没有搜索到相关数据</div>

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
			if(typeof wx != "undefined"){
				wx.ready(function(){
					if(window.__wxjs_environment === 'miniprogram' && '{pigcms{$config.pay_wxapp_paotui_appid}'){
						$('.paotui').show()
						$('.paotui .btn').click(function(){
							wx.miniProgram.navigateTo({url: '/pages/index/location_wxapp?appid={pigcms{$config.pay_wxapp_paotui_appid}&pageTitle='+encodeURIComponent('帮我买')});
							return false;
						});
					}
				});
			}
	
		</script>
	</body>
</html>