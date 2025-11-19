<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$merchant_info['name']}</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/merchant.css?t={pigcms{:time()}"/>
	</head>
	<body>
		<div class="pageBg"></div>
		<scrollView class="page y" id="page">
			<div class="container">
                <if condition="$merchant_info['list_pic']">
                    <div class="sliderBox">
                        <div class="rightPosition merimg-pagination">
                            <span class="now">1</span><span class="all">/{pigcms{$merchant_info['pic_num']}</span>
                        </div>
                        <div class="sliderInner swiper-container" id="merimg">
                            <div class="swiper-wrapper">
                                <volist name="merchant_info['list_pic']" id="item">
                                    <div class="swiper-slide">
                                        <div class="sliderImg" style="background-image:url({pigcms{$item})"></div>
                                    </div>
                                </volist>
                            </div>
                        </div>
                    </div>
                </if>
				<div class="merInfo">
					<div class="merLogo">
                        <if condition="$merchant_info['logo']">
                            <img src="{pigcms{:thumb_img($merchant_info['logo'],148,148,'fill')}"/>
                        <else/>
                            <img src="{pigcms{$static_public}images/nopic.jpg"/>
                        </if>
					</div>
					<div class="merDesc">{pigcms{$merchant_info['s_txt']}</div>
                    <if condition="$merchant_record_num['avatar_info']">
					    <div class="merLookNum">{pigcms{$merchant_record_num['look_num']} 人逛过</div>
                        <div class="avatarList">
                            <volist name="merchant_record_num['avatar_info']" id="item">
                                <span class="item" style="background-image:url({pigcms{$item.avatar});"></span>
                            </volist>
                        </div>
                    </if>
				</div>
				<div class="storeList">
                    <if condition="$merchant_store_list">
                        <volist name="merchant_store_list" id="vo">
                            <div class="item">
                                <div class="storeName"><span class="name">{pigcms{$vo.name}</span><if condition="strip_tags($vo['work_time']) neq '-'"><span class="clock">{pigcms{$vo.work_time}</span></if></div>
                                <div class="storeAddress">{pigcms{$vo.adress}</div>
                                <div class="phoneNavi">
                                    <div class="phone iconfont fl" data-phone="{pigcms{$vo.phone}">&#xe615;</div>
                                    <div class="navi iconfont fl" data-lng="{pigcms{$vo.long}" data-lat="{pigcms{$vo.lat}" data-name="{pigcms{$vo.name}" data-address="{pigcms{$vo.adress}">&#xe606;</div>
                                </div>
                            </div>
                        </volist>
                    </if>
				</div>
                <if condition="$index_sort_group_list">
                    <div class="goodListBox list">
                        <div class="ConTitle">精选推荐</div>
                        <div class="ConDesc">为您精选品质好产品</div>
                        <scrollView class="goodList">
                            <volist name="index_sort_group_list" id="item">
                                <div class="goodItem link newWin" data-url="{pigcms{$item['detail_url']}">
                                    <div class="goodImage" style="background-image:url({pigcms{$item['list_pic']});"></div>
                                    <div class="goodName">{pigcms{$item['group_name']} | {pigcms{$item.s_name}</div>
                                    <div class="goodDesc">{pigcms{$item['intro']}</div>
                                    <div class="goodPrice">{pigcms{$config['Currency_symbol']}{pigcms{$item['price']}<span class="unit">/份</span><span class="oldPrice">{pigcms{$config['Currency_symbol']}{pigcms{$item['old_price']}</span><div class="fr buy">去购买</div></div>
                                </div>
                            </volist>
                        </scrollView>
                    </div>
                </if>
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
				"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('merchant',array('mer_id'=>$_GET['mer_id']))}",
				"tTitle": "我推荐{pigcms{$merchant_info['name']}的所有优惠活动",
				"tContent": "{pigcms{$merchant_info['s_txt']|default=$config['site_name']}"
			};
            var get_route = "{pigcms{$config['site_url']}/wap.php?g=Wap&c=Groupnew&a=get_route";
		</script>
		{pigcms{$shareScript}
		
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/swiper.min.js" charset="utf-8"></script>
		
		<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
		
		<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
		
        <script type="text/javascript" src="{pigcms{$static_path}js/jquery.cookie.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?214" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}groupnew/js/base.js?t={pigcms{:time()}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}groupnew/js/merchant.js?t={pigcms{:time()}" charset="utf-8"></script>
	</body>
</html>