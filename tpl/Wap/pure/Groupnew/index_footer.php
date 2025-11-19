<footer class="footerMenu">
	<ul>
		<li class="tabBtn" data-page="pageIndex">
			<p class="icon iconfont">&#xe7a7;</p>
			<p>首页</p>
		</li>
		<li class="tabBtn" data-page="pageFind">
			<p class="icon iconfont find">&#xe74c;</p>
			<p>发现</p>
		</li>
		<li class="tabBtn" data-page="pageMy">
			<p class="icon iconfont my">&#xe773;</p>
			<p>我的</p>
		</li>
	</ul>	
</footer>
<div class="merchantIncomeBox link newWin pageChild pageIndexChild hide" data-url="{pigcms{$config.site_url}/packapp/merchant/index.html" style="display: none">
	<img src="{pigcms{$static_path}groupnew/images/merchant_income.png"/>
</div>

<div class="pageWxappWechatBox pageChild pageFindChild hide">
	<img src="{pigcms{$static_path}groupnew/images/inviteWechat.png"/>
</div>
<div class="pageWechatQrcodeBox pageChild pageFindChild hide">
	<img src="{pigcms{$config.wechat_qrcode}"/>
</div>

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
<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/swiper.min.js" charset="utf-8"></script>

<!-- minirefresh -->
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/minirefresh.min.css"/>
<script type="text/javascript" src="{pigcms{$static_public}minirefresh/minirefresh.min.js" charset="utf-8"></script>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.css"/>
<script type="text/javascript" src="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.js" charset="utf-8"></script>

<!-- layer -->
<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/fastclick.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>

<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>

<script type="text/javascript" src="{pigcms{$static_path}groupnew/js/base.js?t={pigcms{:time()}" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}groupnew/js/index.js?t={pigcms{:time()}" charset="utf-8"></script>