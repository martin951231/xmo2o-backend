<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="white">
<meta name="format-detection" content="telephone=no">
<title>{pigcms{$app_pack_config['plat_android_name']|default='平台APP'}</title>
<link rel="stylesheet" type="text/css" href="/app_down/wap/css/base.css">
<link rel="stylesheet" type="text/css" href="/app_down/wap/css/download.css">
<link rel="stylesheet" type="text/css" href="/app_down/wap/css/idangerous.swiper.css">

<script type="text/javascript" src="/app_down/wap/js/jquery-1.7.1.min.js"></script>
<script src="/app_down/wap/js/download.js"></script>
<script src="/app_down/wap/js/fakeLoader.min.js"></script>
<script>
        $(document).ready(function(){
            $(".fakeloader").fakeLoader({
                timeToHide:1200,
                bgColor:"#2ecc71",
                spinner:"spinner1",
  	   
            });
        });
    </script>
    <link rel="stylesheet" href="/app_down/wap/css/fakeLoader.css">
 <div class="container">
    <div class="fakeloader"></div>
</div> 

<body>
<div  class="swiper-container screen">
<div class="layer"></div>
<aside class="download_content">
    <div class="jiandou"></div>
    <ul>
        <li><i>1</i>点击右上角<span><img src="/app_down/wap/images/1.png"></span>按钮</li>
        <li><i>2</i>选择<img src="/app_down/wap/images/2.png"></li>
        <li><i>3</i>点击下载按钮进行下载</li>
        <li>知道了</li>
    </ul>
</aside>
<article class="swiper-wrapper">
    <section class="header swiper-slide section-1 swiper-slide-visible swiper-slide-active">
        <div class="banner">
            <div class="banner_content">
				<div class="logo">
					<if condition="$config['wechat_share_img']">
						<img src="{pigcms{:replace_file_domain($config['wechat_share_img'])}"/>
					<else/>
						<img src="/app_down/wap/images/downapp-1_03.png"/>
					</if>
				</div>
                <h1>{pigcms{$app_pack_config['plat_android_name']|default='平台APP'}</h1>
                <p>找美食、叫外卖、看电影、预约服务、生活缴费<br>
                    随时随地，时尚生活由您一键获取</p>
            </div>
        </div>
    </section>
    <section class="swiper-slide section-2 ">
    <div class="swiper_content">
        <h2>掌上城市</h2>
        <p>网罗全城生活快报  搜集身边优惠信息</p>
        <div class="section_img"><img src="/app_down/wap/images/downapp-1_23.png"></div>
        </div>
    </section>
    <section  class="swiper-slide section-3 ">
        <div class="swiper_content">
        <h2>省钱宝典</h2>
        <p>有团购 叫外卖 玩活动 我们不止省钱那么简单</p>
        <div class="section_img"><img src="/app_down/wap/images/downapp-1_36.png"></div>
        </div>
    </section>
    <section  class="swiper-slide section-4 ">
        <div class="swiper_content">
        <h2>预约服务</h2>
        <p>约家政 约美容 约健身 更多服务快人一步</p>
        <div class="section_img"><img src="/app_down/wap/images/downapp-1_45.png"></div>
        </div>
    </section>
    <section  class="swiper-slide section-5 ">
        <div class="swiper_content">
        <h2>支付生活</h2>
        <p>线上线下多种支付途径  线上生活缴费一应俱全</p>
        <div class="section_img"><img src="/app_down/wap/images/downapp-1_53.png"></div>
        </div>
    </section>
	<section class="swiper-slide section-6" ></section>
</article>
<div class="swiper-pagination"></div>
</div>
<script src="/app_down/wap/js/idangerous.swiper-2.0.min.js"></script> 
<script>
    $(function(){
        $(".screen").height($(window).height());
        $(".swiper-slide").height($(window).height());
    })
	var window_height=$(window).height();
 
	
	/*  var mySwiper = new Swiper('.swiper-container', {
        pagination: '.swiper-pagination',
        slidesPerView: 3,
        paginationClickable: true,
        spaceBetween: 30
    });	*/
   var mySwiper = new Swiper('.swiper-container',{
        mode: 'vertical',
		 pagination: '.swiper-pagination',
        paginationClickable: true,
        onSlideNext:function(swiper){
            if(swiper.activeIndex ==5){
                window.location.reload();
            }
        }
    })
	var i=0; 
 
</script>
<footer>
	<if condition="$app_pack_config['plat_weixin_open_appid']">
		<wx-open-launch-app
			id="launch-btn"
			appid="{pigcms{$app_pack_config['plat_weixin_open_appid']}"
			extinfo="{pigcms{:strval($_GET['extinfo'])}"
			style="width:150px;"
		>
			<template>
				<style>
				.btn {
					margin: 0;
					padding: 0;
					border: none;
					font-family: Microsoft YaHei;
					outline: 0;

					font-size: 15px;
					color: #fff;
					width: 135px;
					height: 48px;
					text-align: center;
					line-height: 48px;
					background: #1aaefb;
					border-radius: 3px;
					
					width: 150px;
				}
				.btn  i {
					width: 26px;
					height: 26px;
					display: inline-block;
					background: url({pigcms{$config.site_url}/app_down/wap/images/down_icon_61.png);
					background-size: 26px;
					margin-top: -5px;
					vertical-align: middle;
					margin-right: 5px;
				}
				.btn.ios i {
					background-image: url({pigcms{$config.site_url}/app_down/wap/images/down_icon_61.png);
				}
				.btn.android i {
					background-image: url({pigcms{$config.site_url}/app_down/wap/images/down_icon_63.png);
				}
				</style>
				<button class="btn {pigcms{:(strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'android') !== false ? 'android' : 'ios')}"><i></i>立即打开</button>
			</template>
		</wx-open-launch-app>
	</if>
    <if condition="$app_config['isIos']">
        <button class="iosapp_down" url="{pigcms{$app_config.ios_download_url}"><i></i>前往下载</button>
        <elseif condition="$app_config['android_download_url']"/>
        <button class="android_down" url="{pigcms{$app_config.android_download_url}"><i></i>前往下载</button>
    </if>
</footer>
{pigcms{$shareScript}
<script>
	var btn = document.getElementById('launch-btn');
	btn.addEventListener('launch', function (e) {
		console.log('success');
	});
	btn.addEventListener('error', function (e) {
		if(!e.detail || e.detail.errMsg == 'launch:fail'){
			$('footer>button').trigger('click');
		}
	});
</script>
</body>
</html>