<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$config.group_alias_name}搜索</title>
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
			var location_url="{pigcms{:U('Groupservice/search',array('w'=>urlencode($keywords)))}",now_sort="<if condition="!empty($now_sort)">{pigcms{$now_sort}<else/>defaults</if>";
			var voice_type = "group";
			var main_color = "{pigcms{$config["mobile_head_color"]}";
		</script>
		<script type="text/javascript" src="{pigcms{$static_path}js/groupsearch.js?2110" charset="utf-8"></script>
		<style type="text/css">
		.line_m{text-decoration:line-through;display: inline;color: #ccc}
        .tip_div {
            max-height: 56px;
            overflow: hidden;
        }
        .line-tip {
            color: #06c1ae;
            border: #06c1ae 1px solid;
            display: inline-block;
            border-radius: 5px;
            padding: 1px 3px;
            font-size: 10px;
        }
        .tip_div .tip-info {
            font-size: 10px;
            display: inline-block;
            margin-right: 5px;
            margin-top: 5px;
        }
		</style>
	</head>
	<body>
		<section class="searchBar pageSliderHide <if condition="!$is_weixin_browser">wap</if>" style="background-color:white;border-bottom:1px solid #edebeb;">
			<div class="searchBox">
				<form id="search-form" action="{pigcms{:U('Search/group')}" method="post">
					<input type="search" id="keyword" name="w" placeholder="请输入搜索词" autocomplete="off" value="{pigcms{$keywords}"/>
				</form>
			</div>
			<div class="voiceBtn"></div>
		</section>
		<section class="searchBox pageSliderHide">
			<ul>
				<li class="dropdown-toggle link-url" data-url="{pigcms{:U('Search/shop',array('w'=>urlencode($keywords)))}" target="self"><span class="nav-head-name">{pigcms{$config.shop_alias_name}</span></li>
				<li class="dropdown-toggle active" data-url-type="openLeftWindow" style='color:{pigcms{$config["mobile_head_color"]};border-color:{pigcms{$config["mobile_head_color"]}'><span class="nav-head-name">{pigcms{$config.group_alias_name}</span></li>
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
				<section class="navBox">
					<ul style="border-top: 1px solid #edebeb;">
						<li class="dropdown-toggle caret sort <if condition="$now_sort eq 'default'">active</if>" style="color:<if condition="$now_sort eq 'default'">{pigcms{$config["mobile_head_color"]}</if>" data-sort="default"><span class="nav-head-name">默认排序</span><i class="icon iconfont bottom">&#xe791;</i><i class="icon iconfont top">&#xe603;</i></li>
						<li class="dropdown-toggle caret sort <if condition="$now_sort eq 'hot'">active</if>" style="color:<if condition="$now_sort eq 'hot'">{pigcms{$config["mobile_head_color"]}</if>" data-sort="hot"><span class="nav-head-name">销量最高</span><i class="icon iconfont bottom">&#xe791;</i><i class="icon iconfont top">&#xe603;</i></li>
						<li class="dropdown-toggle caret sort <if condition="$now_sort eq 'price'">active</if>" style="color:<if condition="$now_sort eq 'price'">{pigcms{$config["mobile_head_color"]}</if>" data-sort="price"><span class="nav-head-name">价格最低</span><i class="icon iconfont bottom">&#xe791;</i><i class="icon iconfont top">&#xe603;</i></li>
					</ul>
				</section>
				<script id="groupListBoxTpl" type="text/html">
					{{# for(var i = 0, len = d.group_list.length; i < len; i++){ }}
						<dd class="link-url" data-url="{{ d.group_list[i].url }}">
							<div class="dealcard-img imgbox" style="width:76px;height:76px;">
								<img src="{{ d.group_list[i].list_pic }}" alt="{{ d.group_list[i].s_name }}" style="margin-left:0px;"/>
							</div>
							<div class="dealcard-block-right">
								<div class="brand">{{# if(d.group_list[i].tuan_type != 2){ }} {{ d.group_list[i].merchant_name }} {{# }else{ }} {{ d.group_list[i].s_name }} {{# } }}</div>
								<div class="title" style="padding-left:0;">{{ d.group_list[i].group_name }}</div>
								<div class="price">
									<strong>{pigcms{$config['Currency_symbol']}{{ d.group_list[i].price }}</strong>{{# if(d.group_list[i].wx_cheap){ }}<span class="tag">微信再减{pigcms{$config['Currency_symbol']}{{ d.group_list[i].wx_cheap }}</span>{{# }else{ }}<del>{{ d.group_list[i].old_price }}</del>{{# } }} <span class="line-right">{{ d.group_list[i].sale_txt }}</span>
								</div>
                                <div class="tip_div">
                                    {{# if(d.group_list[i].field_info) {}}
                                    <div class="tip-info">
                                        {{# for (var n = 0, nlen = d.group_list[i].field_info.length; n < nlen; n++){ }}
                                        <div class="line-tip">{{ d.group_list[i].field_info[n] }}</div>
                                        {{# } }}
                                    </div>
                                    {{# } }}
                                </div>
							</div>
						</dd>
					{{# } }}
				</script>
				<section class="listBox">
					<dl class="dealcard">
						<volist name="group_list" id="vo">
							<dd class="link-url" data-url="{pigcms{$vo.url}">
							<php> if($vo['pin_num'] > 0){ </php><div class="pin_style"></div> <php> }  </php>
								<div class="dealcard-img imgbox" style="width:76px;height:76px;">
									<img src="{pigcms{:thumb_img($vo['list_pic'], 200, 200, 'fill')}" alt="{pigcms{$vo.s_name}" style="margin-left:0px;"/>
								</div>
								<div class="dealcard-block-right">
									<div class="brand"><if condition="$vo['tuan_type'] neq 2">{pigcms{$vo.merchant_name}<else/>{pigcms{$vo.s_name}</if></div>
									<div class="title" style="padding-left:0;">{pigcms{$vo.group_name}</div>
									<div class="price">
										<strong>{pigcms{$config['Currency_symbol']}{pigcms{$vo.price}</strong><span class="strong-color"><if condition="$vo.extra_pay_price neq ''">{pigcms{$vo.extra_pay_price}</if></span><div class="line_m" style="margin-left:6px;">{pigcms{$config['Currency_symbol']}{pigcms{$vo.old_price}<if condition="$vo.extra_pay_price neq ''">{pigcms{$vo.extra_pay_price}</if></div> <if condition="$vo['wx_cheap']"><span class="tag">微信再减{pigcms{$config['Currency_symbol']}{pigcms{$vo.wx_cheap}</span><else/></if> <span class="line-right">{pigcms{$vo['sale_txt']}</span>
									</div>
                                    <if condition="$vo['field_info']">
                                        <div class="tip_div">
                                            <volist name="vo['field_info']" id="vv">
                                                <div class="tip-info">
                                                    <div class="line-tip">
                                                        {pigcms{$vv}
                                                    </div>
                                                </div>
                                            </volist>
                                        </div>
                                    </if>
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