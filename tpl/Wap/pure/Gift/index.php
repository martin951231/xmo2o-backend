<!DOCTYPE html>
<html lang="en">
<head>
    <title>{pigcms{$config.gift_alias_name}首页</title>
<include file="Public:gift_header" />
<script type="text/javascript" language="javascript">
var ajax_gift_list_url = "{pigcms{:U('ajax_gift_list')}";
var gift_list_url = "{pigcms{:U('gift_list')}";
var gift_detail_url = "{pigcms{:U('gift_detail')}"
var  score_name = "{pigcms{$config.score_name}";
var currency_symbol = "{pigcms{$config.currency_symbol|default='元'}";
</script>
<style type="text/css" language="javascript">
.rectMod{ height:100%}
.swiper-container-horizontal>.swiper-pagination{bottom: 2px!important;}
</style>
<body>
<div class="lodingCover">
    <div class="spinner">
        <div class="rect1"></div>
        <div class="rect2"></div>
        <div class="rect3"></div>
        <div class="rect4"></div>
        <div class="rect5"></div>
    </div>
</div>
<if condition="empty($_GET['from_sign'])">
	<section class="top">
		<div class="wrap clearfix seacrh_txtGo"> 
			<label class="seacrh_txt" >搜索商品</label> 
		   <!-- <a href="##" class="fr topBtn"><i class="fa fa-search"></i></a> -->
		</div>
	</section>
	<section style="height:50px;width:100%"></section>
	<if condition='!empty($gift_adver_list)'>
		<section class="scroll" id="banner_hei" >
			<div class="swiper-container swiper-container-banner"  style="height: 100%;">
				<div class="swiper-wrapper">
					<volist name='gift_adver_list' id='slider'>
						<div class="swiper-slide" onclick="window.open('{pigcms{$slider.url}')">
							<img style="height:100%" src="{pigcms{$slider.pic}" >
						</div>
					</volist>
				</div>
				<div class="swiper-pagination swiper-pagination-banner"></div>
			</div>	
		</section>
	</if>
</if>


<if condition='!empty($now_user)'>
<nav class="topNav indexNav">
    <ul class="box">
        <li class="b-flex">
            <if condition="$config['open_user_money'] eq 0 and $now_user['now_money'] eq 0">
                <a href="{pigcms{:U('My/integral')}">
            <else/>
                <a href="{pigcms{:U('My/my_money')}">
            </if>
                <i style="color: #EC702C;">{pigcms{$now_user['score_count']|getFormatNumber=###}</i><span style="display: block;color: #333;">我的{pigcms{$config.score_name}</span>
            </a>
        </li>
        <li class="b-flex">
            <a href="<?php echo C('config.site_url').D('House_village')->base_url;?>pages/my/my_order?state=">
                <i><img style="width: 1.2rem;height: 1.2rem" src="{pigcms{$static_path}gift/images/gift_zd.png"/></i><span style="display: block;;color: #333;">{pigcms{$config.score_name}订单</span>
            </a>
        </li>
        <li class="b-flex">
            <a href="{pigcms{:U('My/integral')}">
                <i><img style="width: 1.2rem;height: 1.2rem;" src="{pigcms{$static_path}gift/images/gift_mx.png"/></i><span style="display: block;;color: #333;">{pigcms{$config.score_name}明细</span>
            </a>
        </li>
		<if condition="C('config.gift_alias_name') neq ''">
		<li class="b-flex">
            <a href="{pigcms{:U('fast_gift')}">
                <i><img style="width: 1.2rem;height: 1.2rem;" src="{pigcms{$static_path}gift/images/gift_wn.png"/></i><span style="display: block;;color: #333;">我能兑换</span>
            </a>
        </li>
		</if>
    </ul>
</nav>
</if>

<section class="index3rect">
    <if condition="count($gift_adver) egt 3"> 
		<div class="leftRect">
			<div class="r1 fl pr rectMod">
				<a href="{pigcms{$gift_adver[0]['url']}" title="{pigcms{$gift_adver[0]['name']}">
					<img class="pa" src="{pigcms{$gift_adver[0]['pic']}"/>
				</a>
			</div>
			<div class="ofh br-l-eee">
				<div class=" r2">
					<a href="{pigcms{$gift_adver[1]['url']}" title="{pigcms{$gift_adver[1]['name']}">
						<div class=" pr rectMod">
							<img class="pa" src="{pigcms{$gift_adver[1]['pic']}"/>
						</div>
					</a>
				</div>
				<div class="r2">
					<a href="{pigcms{$gift_adver[2]['url']}" title="{pigcms{$gift_adver[2]['name']}">
						<div class="br-t-eee pr rectMod">
							<img class="pa" src="{pigcms{$gift_adver[2]['pic']}"/>
						</div>
					</a>
				</div>
			</div>
		</div>
    </if>
</section>

<section class="scrollList" style="margin-top:1px;">
    <section class="navThis pr">
        <nav class="scrollNav pa" id="scrollerBox">
            <div class="scrollerIn">
                <ul class="clearfix">
					<volist name='gift_category_list["list"]' id='gift_category'>
						<li data-cat-fid="{pigcms{$gift_category['cat_id']}" <if condition='$i eq 1'>class="on"</if>>
							<a>{pigcms{$gift_category['cat_name']}</a>
						</li>
					</volist>
                </ul>
            </div>
        </nav>
    </section>
        <div class="content-padded">
            <div class="row no-gutter" id="gift_list">
            </div>
        </div>
</section>
<include file="Public:gift_footer" />
<script>
    var swiper = new Swiper('.swiper-container-banner', {
        loop:true,
        autoplay: 5000,//可选选项，自动滑动
        // 如果需要分页器
        pagination: '.swiper-pagination-banner'
    });
</script>
<script type="text/javascript">
    //滚动导航
    $(function(){
		$('.scrollNav ul li').css({'width':'auto','padding':'0 8px'});
		var scrollWidth = 0;
		$.each($('#scrollerBox li'),function(i,item){
            scrollWidth += $(item).width() + 11;
		});
		$('.navThis .scrollerIn').width(scrollWidth);
		$('.navThis').css({'overflow-x':'auto'});
		var cat_fid = $(".scrollNav ul li.on").attr('data-cat-fid');
		get_gift_list(cat_fid,score_name);
    });

	if($('#banner_hei').size() > 0){
		var banner_height	=	$(window).width()/320;
		banner_height	=	 Math.ceil(banner_height*119);
		// $('#banner_hei').height(banner_height);
	}
	
	$('.seacrh_txtGo').click(function(){
		 window.location.href="{pigcms{$config.site_url}{pigcms{:U('Gift/search')}";
	})
</script>

<script type="text/javascript">
window.shareData = {
	"moduleName":"Gift",
	"moduleID":"0",
	"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
	"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Gift/index')}",
	"tTitle": "{pigcms{$config.gift_alias_name}首页",
	"tContent": "{pigcms{$config.site_name}"
};
</script>
{pigcms{$shareScript}
	{pigcms{$coupon_html}
</body>
</html>