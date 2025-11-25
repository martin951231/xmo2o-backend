<script>
	window.shareData = {
		"moduleName":"Home",
		"moduleID":"0",
		"imgUrl": "<if condition="$config['village_group_wx_share_img']">{pigcms{$config.village_group_wx_share_img}<elseif condition="$config['wechat_share_img']"/>{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
		"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('index',array('leader_id'=>$_GET['leader_id']))}",
		"tTitle": "{pigcms{$config['village_group_wx_share_title']|default=$config['village_group_alias']}",
		"tContent": "<if condition="$config['village_group_wx_share_des']">{pigcms{$config.village_group_wx_share_des}<else/>{pigcms{$config.site_name}</if>",
		"wxapp_tTitle": "{pigcms{$config.village_group_wxapp_share_title|trim=###}",
		"wxapp_Pic": "{pigcms{$config.village_group_wxapp_share_img|trim=###}",
		"wxapp_Url": "/pages/index/index?redirect=webview&webview_url={pigcms{:urlencode($config['site_url'].U('index',array('leader_id'=>$_GET['leader_id'],'openid'=>$_SESSION['openid'])))}&webview_title={pigcms{:urlencode($config['village_group_alias'])}",
	};
</script>
<div class="share_html">{pigcms{$shareScript}</div>

<div id='shareIcon' class="link newWin" data-url="{pigcms{:U('imgBoxShare',array('leader_id'=>$_GET['leader_id']))}"></div>
<div id='indexShareMask' class="hidden"></div>
<div id='scanCodeMaskFixed'></div>
<div id='GradeFixed'></div>
<div class="shareMaskCommon"><img src="{pigcms{$static_path}villagegroup/images/shareMask.png"></div>
<div id='modifyAddressesFixed'></div> 
<div id='searchAddressFixed'></div>
<link href="{pigcms{$static_path}css/check.css" rel="stylesheet"/>
<style>
	.my-account {
		color: #333;
		position: relative;
		display: block;
		width:100%;
		position: relative;
		height:11.5rem;
		background-color:#06c1ae;
	}
	.account-bg{
		position: absolute;
		top: 0;
		left: 0;
		height: 100%;
		width: 100%;
		z-index: -1;
	}
	.my-account>img {
		height: 100%;
		position: absolute;
		right: 0;
		top:0;
		z-index: 0;
	}
	.my-account .user-info {
		z-index: 1;
		position: absolute;
		/*top: 1.5em;
        left: 6em;*/
		top: 2rem;
		left: 70px;
		box-sizing: border-box;
		padding-left: 1.9em;
		font-size: 13px;
		color: #666;
	}
	.my-account .uname {
		font-size: 18px;
		color: #fff;
		margin-top: .1em;
		margin-bottom: .2em;
	}
	.my-account .umoney {
		color: #fff;
		margin-bottom: 0.06em;
	}
	.my-account .avatar_box{
		position: absolute;
		top: 2em;
		left: 1em;
		width: 5em;
		height: 5em;
		z-index:1;
		border-radius:100%;
		border:1px solid #fff;
		-moz-border-radius:100%;
		-webkit-border-radius:100%;
		overflow:hidden;
	}
	.my-account .avater {
		width:100%;
		height:100%;
	}
	.phone{
		float:left;
		z-index:100;
	}
	.data{
		position: absolute;
		top: 2.8em;
		left: 9em;
		padding:2px 10px;
		border:1px solid #fff;
		border-radius:15px;
		color:#fff;
		width:80px;
		text-align:center;
		margin-top:-7px;
		z-index:100;
		margin-left:10px
	}
	.titleImg{
		width:22px;
		height:22px;
		margin-right:10px;
	}
	.words{
		text-align:center;
		color:#5f5f5f;
		font-size:12px;
	}
	.wh25{
		width:25px;
		height:25px;
	}
	.foloow{
		float:left;
		width:25%;
		text-align:center;
		color:#5f5f5f;
		font-size:12px;
	}
	.foloow_b{
		border-left:1px solid #e5e5e5;
	}
	.padd7{
		padding:7px;
	}
	#close_eye img{
		width: 16%;
		height: 16%;
	}
	.set_up div {

		color:#fff;
	}
	.noPhone{
		padding: 2px 8px;
		background: #999 !important;
		width: auto !important;
		color: #fff !important;
		margin-right: 10px;
		border-radius: 49px;
		opacity: 0.8;
		height: 21px;
		margin-top: -3px;
		line-height: 21px;
	}
	.bd_color{font-weight:600;font-size:18px}
	.pd-t7{padding:0px 7px 0px 7px}
	.mask {
		background:rgba(0, 0, 0, .5);
		z-index:99999;
		position:fixed;
		top:0;
		left:0;
		right:0;
		bottom:0;
		height:100%;
	}
	.contact-box .detail {
		background:#edecec;
		width:80%;
		position:absolute;
		top:50%;
		left:50%;
		transform:translate(-50%,-50%);
		padding:40px 30px 10px;
		box-sizing:border-box;
		border-radius:5px;

	}

	.contact-box .detail .item {
		background:#fff;
		margin:0 auto 20px;
		padding:6px 20px;
		font-size:16px;
		color:#06c1ae;
		display:flex;
		align-items:center;
		border-radius:5px;

	}
	.contact-box .detail .item.active {
		color:#999;
	}

	.contact-box .icon-cha {
		position:absolute;
		top:7px;
		right:7px;
		color:rgba(0, 0, 0, .3);

	}
	.contact-box .detail .item {
		background:#fff;
		margin:0 auto 20px;
		padding:6px 40rpx;
		font-size:16px;
		color:#06c1ae;
		display:flex;
		align-items:center;
		border-radius:5px;

	}
	.my-account{
		height: 11.5rem;
	}
	#money.bt{
		border-top-right-radius: 10px;
		border-top-left-radius: 10px;
	}
	#money.b{
		border-radius: 10px;

	}
	.laba{
		border-bottom-right-radius: 10px;
		border-bottom-left-radius: 10px;
		height: 16px;
		margin-bottom: 10px;
		padding: 10px 15px;
		background: #ffffff;
		margin-top: 0.75rem;
		margin-left: 10px;
		margin-right: 10px;
		position:relative
	}

	.weui-cells:before {
		content: " ";
		position: absolute;
		left: 0;
		right: 0;
		height: 1px;
		color: #e5e5e5;
		z-index: 2;
		top: 0;
		border-top: 1px solid #e5e5e5;
		-webkit-transform-origin: 0 100%;
		transform-origin: 0 100%;
		-webkit-transform: scaleY(.5);
		transform: scaleY(.5);
	}
	.weui-cell:after {
		content: " ";
		position: absolute;
		left: 0;
		right: 0;
		height: 1px;
		color: #e5e5e5;
		z-index: 2;
		bottom: 0;
		border-top: 1px solid #e5e5e5;
		-webkit-transform-origin: 0 100%;
		transform-origin: 0 100%;
		-webkit-transform: scaleY(.5);
		transform: scaleY(.5);
	}
	.rel{position:relative}


	.weui-cell__ft {
		text-align: right;
		color: #999;
		padding-right: 13px;
		position: relative;
	}
	.weui-cell__ft:after {
		content: " ";
		display: inline-block;
		height: 6px;
		width: 6px;
		border-width: 2px 2px 0 0;
		border-color: #c8c8cd;
		border-style: solid;
		-webkit-transform: matrix(.71,.71,-.71,.71,0,0);
		transform: matrix(.71,.71,-.71,.71,0,0);
		position: absolute;
		top: 50%;
		margin-top: -16px;
		right: 4px;
	}
	#my_order_box>div:nth-child(4n+1){
		clear: both;
	}
	.flex{
		display: -webkit-box; /* 老版本语法: Safari, iOS, Android browser, older WebKit browsers. */
		display: -moz-box; /* 老版本语法: Firefox (buggy) */
		display: -ms-flexbox; /* 混合版本语法: IE 10 */
		display: -webkit-flex; /* 新版本语法: Chrome 21+ */
		display: flex; /* 新版本语法: Opera 12.1, Firefox 22+ */
	}
	.flex-wrap{
		-webkit-flex-wrap: wrap;
		-moz-flex-wrap: wrap;
		-ms-flex-wrap: wrap;
		-o-flex-wrap: wrap;
		flex-wrap:wrap;
	}

</style>
<!-- 店铺联系方式弹窗 -->
<div class='mask contact-box' style="display:none">
	<div class='detail'>
		<volist name="kefu.phone" id="vo">
			<a class='item' href="tel:{pigcms{$vo}">
				<img src="/static/images/my_new/icon_tel.png" style="width:26px;height:26px"/>
				<span style="padding-left:10px">{pigcms{$vo}</span>
			</a>
		</volist>
		<if condition="$kefu['url'] neq ''">
			<a class='item'  href="{pigcms{$kefu.url}" >
				<img src="/static/images/my_new/icon_weixin1.png" style="width:26px;height:26px"/>
				<span style="padding-left:10px">在线客服</span>
			</a>
			<else/>
			<a class='item'  href="javascript:;" >
				<img src="/static/images/my_new/icon_weixin.png" style="width:26px;height:26px"/>
				<span style="padding-left:10px;color:#999" >暂未开通在线客服</span>
			</a>
		</if>
		<img src="/static/images/my_new/icon_del.png" style="width:26px;height:26px;cursor:pointer" class="icon-cha" />
	</div>
</div>
<footer class="footerMenu">
	<div class="total-box ks-clear">
		<span>合计：<span class="red" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};</if>">{pigcms{$config['Currency_symbol']}</span><span class="red font_size16 price_total" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};</if>">16.39</span></span>
		<div class="btn"  id="join_pay" style="<if condition='$config["mobile_head_color"]'>background-color: {pigcms{$config["mobile_head_color"]};</if>"><a href="javascript:void(0)">去结算(<b id="cate_count">0</b>)</a></div>
	</div>

	
  
	<if condition="$open eq 1">
	<ul>
		<li class="tabBtn" data-page="pageIndex">
			<p class="icon iconfont">&#xe645;</p>
			<p>首页</p>
		</li>
		<li class="tabBtn cart" data-page="pageCart">
			<p>
				<span class="num-box">
					<i class="icon iconfont">&#xe628;</i>
					<span class="num goods_num" style="<if condition='$config["mobile_head_color"]'>background: {pigcms{$config["mobile_head_color"]};</if>">0</span>
				</span>
			</p>
			<p class="">购物车</p> 
		</li> 
		<li class="tabBtn my_option" >
			<p class="icon iconfont kf">&#xe6a7;</p>
			<p>客服</p>
		</li> 
		<li class="tabBtn" data-page="pageMy">
			<p class="icon iconfont my">&#xe619;</p>
			<p>我的</p>
		</li>
	</ul>
	<else/>
	<ul class="fen_san">
		<li class="tabBtn" data-page="pageIndex">
			<p class="icon iconfont">&#xe645;</p>
			<p>首页</p>
		</li>
		<li class="tabBtn cart" data-page="pageCart">
			<p>
				<span class="num-box">
					<i class="icon iconfont">&#xe628;</i>
					<span class="num goods_num" style="<if condition='$config["mobile_head_color"]'>background: {pigcms{$config["mobile_head_color"]};</if>">0</span>
				</span>
			</p>
			<p class="">购物车</p> 
		</li> 
		<li class="tabBtn" data-page="pageMy">
			<p class="icon iconfont my">&#xe619;</p>
			<p>我的</p>
		</li>
	</ul>	

	</if>
</footer>

<!--div class="merchantIncomeBox link newWin pageChild pageIndexChild hide" data-url="{pigcms{$config.site_url}/packapp/merchant/index.html">
	<img src="{pigcms{$static_path}villagegroup/images/merchant_income.png"/>
</div-->


<script type="text/javascript">
	var all_money = 0,num=0,cart_cookie='',goodsCart = {},can_pay = true,start_id='{pigcms{$_GET['leader_id']}';
	var get_cart_url="{pigcms{:U('ajax_cart_get')}";
	var save_cart_url="{pigcms{:U('ajax_cart_save')}";
	var save_group_order="{pigcms{:U('save_village_group_order')}";
	var verify_url ="{pigcms{:U('leaderOrder')}";
	var goods_cat_url ="{pigcms{:U('get_goods_by_cat_id')}";
	var site_url ="{pigcms{$config.site_url}";
	var check_order ="{pigcms{:U('check_order')}";
	var leader_id ='{pigcms{$_GET['leader_id']}';
	var leaderlist_url ='{pigcms{:U('leaderList')}';
	var check_leader ='{pigcms{:U('ajax_leader')}';
	var page =1 ;
	var mobile_color = '{pigcms{$config["mobile_head_color"]}';



</script>

<!-- {pigcms{$shareScript} -->

<!-- 一键复制 -->
<script src="{pigcms{$static_path}villagegroup/js/clipboard.min.js"></script>

<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/swiper.min.js" charset="utf-8"></script>

<!-- minirefresh -->
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/minirefresh.min.css"/>
<script type="text/javascript" src="{pigcms{$static_public}minirefresh/minirefresh.min.js" charset="utf-8"></script>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.css"/>
<script type="text/javascript" src="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.js" charset="utf-8"></script>

<!-- weui -->
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/weui.min.css?t={pigcms{:time()}"/>
<script src="https://res.wx.qq.com/open/libs/weuijs/1.1.3/weui.js"></script>

<!-- layer -->
<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/fastclick.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}villagegroup/js/base.js?t={pigcms{:time()}" charset="utf-8"></script>
<script charset="utf-8">var currency_symbol = "{pigcms{$config.Currency_symbol}";</script>
<script type="text/javascript" src="{pigcms{$static_path}villagegroup/js/index.js?t={pigcms{:time()}" charset="utf-8"></script>

<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/address_9d295cd.css?t=001">
<script type="text/javascript" src="{pigcms{$static_path}js/SelectChar.js?001" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}villagegroup/js/common.js?002"></script>
<script>
	$(".my_option").on('click',function(){
		$(".contact-box").show();
		console.log('box');
	})
	$(".icon-cha").on('click',function(){
		$(".contact-box").hide()

	})
</script>
<if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=true&libraries=places&key={pigcms{$config.google_map_ak}"></script>
<else/>
    <script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
</if>
