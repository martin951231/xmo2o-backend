<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$category_info.cat_name|default='全部分类'}</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/category.css?t={pigcms{:time()}"/>
	</head>
	<body>
		<div class="pageBg"></div>
		<scrollView class="page y" id="page">
			<div class="container">
				<if condition="!$_GET['cat_id']">
					<div class="adverBox hide"></div>
				<elseif condition="$category_info['cat_ad_pic'] neq ''" />
                    <div class="adverBox" style="background-image:url('{pigcms{$category_info.cat_ad_pic}');background-repeat: no-repeat;background-size: 100%;-moz-background-size: 100%;-webkit-background-size: 100%;"></div>
                <else/>
					<div class="adverBox hide"></div>
				</if>
				<scrollView class="categoryBox cf">
					<div class="item <if condition="!$_GET['cat_id']">active</if>" data-url="{pigcms{$all_url}">全部</div>
					<volist name="all_category_list" id="vo">
						<div class="item <if condition="$_GET['cat_id'] eq $vo['cat_id']">active</if>" data-url="{pigcms{$vo['category_url']}" data-adverimg="{pigcms{$vo['cat_ad_pic']}">{pigcms{$vo.cat_name}</div>
					</volist>
				</scrollView>
				<div class="contentBox">
                    <if condition="$all_two_category_group">
                        <volist name="all_two_category_group" id="item">
                            <div class="goodListBox list">
                                <if condition="$item['more']">
                                    <div class="moreGood link newWin" data-title="{pigcms{$item.cat_name}" data-url="{pigcms{$item['more_url']}">查看更多</div>
                                </if>
                                <div class="ConTitle">{pigcms{$item.cat_name}</div>
                                <div class="ConDesc">{pigcms{$item['cat_des']|default=$item['cat_name']}</div>

                                <scrollView class="goodList">
                                    <volist name="item['group_list']" id="vv">
                                        <div class="goodItem link newWin" data-url="{pigcms{$vv['detail_url']}">
                                            <div class="goodImage pr">
												<if condition="$vv['pin_num'] gt 0">
													<div class="pin_style"></div>
												</if>
                                                <img src="{pigcms{:thumb_img($vv['list_pic'], 450, 250, 'fill')}"/>
                                            </div>
                                            <div class="goodName">{pigcms{$vv.merchant_name} | {pigcms{$vv.s_name}</div>
                                            <div class="goodPrice">{pigcms{$config['Currency_symbol']}{pigcms{$vv.price}<span class="unit">/份</span><span class="oldPrice">{pigcms{$config['Currency_symbol']}{pigcms{$vv.old_price}</span></div>
                                        </div>
                                    </volist>
                                </scrollView>
                            </div>
                        </volist>
                        <else/>
                        <div style="padding: 35%;text-align: center;color: #999;">
                            <img src="{pigcms{$static_path}groupnew/images/noData.png"/>
                            <div>暂无数据</div>
                        </div>
                    </if>
				</div>
			</div>
		</scrollView>
		
		<div class="gotopBox hide" id="gotopBox">
			<p class="iconfont">&#xe647;</p>
		</div>
		
		<script type="text/javascript">
			window.shareData = {
				"moduleName":"Home",
				"moduleID":"0",
				"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
				"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Groupnew/category',array('cat_id'=>$_GET['cat_id'],'page_num'=>$_GET['page_num']))}",
				"tTitle": "{pigcms{$category_info.cat_name|default='同城优选'}",
				"tContent": "{pigcms{$config.site_name}"
			};
		</script>
		{pigcms{$shareScript}
		
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
		
		<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
		
		<script type="text/javascript" src="{pigcms{$static_path}groupnew/js/base.js?t={pigcms{:time()}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}groupnew/js/category.js?t={pigcms{:time()}" charset="utf-8"></script>
	</body>
</html>