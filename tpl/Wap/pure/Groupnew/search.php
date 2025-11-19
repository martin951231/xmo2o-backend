<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>搜索</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/search.css?t={pigcms{:time()}"/>
		<script>var searchUrl = "{pigcms{:U('search')}";</script>
	</head>
	<body>
		<div class="pageBg" ></div>
		<scrollView class="page y" >
			<div class="container">
				<div class="flex searchBox">
					<span class="flex-grow1 flex search-input">
						<i class="iconfont icon-search">&#xe622;</i>
						<input class="flex-grow1" type='text' placeholder="搜索商品或店铺..." id="searchTxt" value="{pigcms{$key}"/>
						<i class="iconfont icon-cancel <if condition='!$key'>hide</if>">&#xe61f;</i>
					</span>
					<span class="search-btn" style="background:{pigcms{$config.mobile_head_color}">搜索</span>
				</div>
                <if condition="$group_list || $store_list">
                    <div class="list-wrapper">
                        <div class="list-box">
                            <if condition="$group_list">
                                <div class="searchGoodBox">
                                    <div class="search-title">相关商品</div>
                                    <div>
                                        <volist name="group_list" id="item">
                                            <div class="flex goods-detail newWin link" data-url="{pigcms{$item['detail_url']}">
                                                <div class="flex-shink0 goods-img-box">
													<img class="goods-img" src="{pigcms{:thumb_img($item['list_pic'], 200, 200, 'fill')}"/>
												</div>
                                                <div class="flex flex-grow1 flex-zTopBottom flex-justify info-box">
                                                    <div>
                                                        <div class="ellipsis name">{pigcms{$item['group_name']} | {pigcms{$item['s_name']}</div>
                                                        <div class="gray">{pigcms{$item['intro']}</div>
                                                    </div>
                                                    <div class="flex flex-justify flex-align">
                                                        <div><span class="red now">{pigcms{$config['Currency_symbol']}{pigcms{$item['price']}</span><span class="red">/份</span><span class="gray old">{pigcms{$config['Currency_symbol']}{pigcms{$item['old_price']}</span>
                                                            <if condition="$item['buy_can_get_score_txt']">
                                                            <br/><span style="font-weight: 600;font-size: 12px;">{pigcms{$item['buy_can_get_score_txt']}</span>
                                                            </if>
                                                        </div>
                                                        <div class="click-btn" style="background: {pigcms{$config.mobile_head_color}">立即购买</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </volist>
                                    </div>
                                </div>
                            </if>

                            <if condition="$store_list">
                                <div class="searchStoreBox">
                                    <div class="search-title">相关店铺</div>
                                    <div>
                                        <volist name="store_list" id="store">
                                            <div class="flex flex-align store-detail newWin link" data-url="/packapp/platn/pages/store/v1/home/index?store_id={pigcms{$store['store_id']}">
                                                <img class="flex-shink0 store-img" src="{pigcms{$store['list_pic']}"/>
                                                <div class="info-box">
                                                    <div class="flex flex-align">
                                                        <span class="desc">{pigcms{$store['store_name']}</span>
                                                        <if condition="$store['merchant_record_num']['look_num'] gt 0">
                                                            <span class="flex-shink0 gray">({pigcms{$store['merchant_record_num']['look_num']}人逛过)</span>
                                                        </if>
                                                    </div>
                                                    <div class="gray desc">{pigcms{$store['txt_info']}</div>
                                                </div>
                                                <div class="flex-shink0 click-btn" style="background: {pigcms{$config.mobile_head_color}">进入店铺</div>
                                            </div>
                                        </volist>
                                    </div>
                                </div>
                            </if>
                            <div class="bottom-tip">已经到底了...</div>
                        </div>
                    </div>
				<else/>
                    <div class="list-wrapper" style="padding-top:200px;text-align:center;">未搜索到商品或店铺</div>
                </if>
			</div>
		</scrollView>
		<div id="hideLoad" class="hide"></div>
		
		<script type="text/javascript">
			window.shareData = {
				"moduleName":"Home",
				"moduleID":"0",
				"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
				"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Groupnew/index')}",
				"tTitle": "{pigcms{$config.group_index_name|default='同城优选'}",
				"tContent": "{pigcms{$config.site_name}"
			};
		</script>
		{pigcms{$shareScript}
		
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		
		<!-- minirefresh -->
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/minirefresh.min.css"/>
		<script type="text/javascript" src="{pigcms{$static_public}minirefresh/minirefresh.min.js" charset="utf-8"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.css"/>
		<script type="text/javascript" src="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.js" charset="utf-8"></script>

		<!-- layer -->
		<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
		
		<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
		
		<script type="text/javascript" src="{pigcms{$static_path}groupnew/js/base.js?t={pigcms{:time()}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}groupnew/js/search.js?t={pigcms{:time()}" charset="utf-8"></script>
	</body>
</html>