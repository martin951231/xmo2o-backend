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
		<scrollView class="page y two_category" id="page">
			<div class="container">
				<div class="contentBox">
					<div class="goodListBox list">
						<div class="goodList" id="goodList">
                            <volist name="cat_list['group_list']" id="item">
                                <div class="goodItem link newWin" data-url="{pigcms{$item['detail_url']}">
                                    <div class="goodImage" style="background-image:url({pigcms{$item.list_pic});"></div>
                                    <div class="goodName">{pigcms{$item.name} | {pigcms{$item.s_name}</div>
                                    <div class="goodPrice">{pigcms{$config['Currency_symbol']}{pigcms{$item.price}<span class="unit">/份</span><span class="oldPrice">{pigcms{$config['Currency_symbol']}{pigcms{$item.old_price}</span></div>
                                </div>
                            </volist>
						</div>
					</div>
				</div>
			</div>
		</scrollView>
		<div id="hideLoad" class="hide"></div>
		
		<div class="gotopBox hide" id="gotopBox">
			<p class="iconfont">&#xe647;</p>
		</div>
		
		<script type="text/javascript">
			window.shareData = {
				"moduleName":"Home",
				"moduleID":"0",
				"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
				"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Groupnew/two_category',array('cat_id'=>$_GET['cat_id'],'page_num'=>$_GET['page_num']))}",
				"tTitle": "{pigcms{$category_info.cat_name|default='同城优选'}",
				"tContent": "{pigcms{$config.site_name}"
			};
		</script>
		{pigcms{$shareScript}
		
		<script type="text/javascript">
			var loadMoreUrl = "{pigcms{:U('two_category',array('cat_id'=>$_GET['cat_id'],'page_num'=>$_GET['page_num']))}";
		</script>
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
		<script type="text/javascript" src="{pigcms{$static_path}groupnew/js/two_category.js?t={pigcms{:time()}" charset="utf-8"></script>
	</body>
</html>