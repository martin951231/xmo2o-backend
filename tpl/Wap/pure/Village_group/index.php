<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$config['village_group_alias']}</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=yes"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/index.css?t={pigcms{:time()}"/>
        <script type="text/javascript">
            var nowPage = 'pageIndex'; var main_color='{pigcms{$config["mobile_head_color"]}';
            var map_config = "{pigcms{$config['map_config']}";
            var google_map_ak = "{pigcms{$config['google_map_ak']}";
            var lat = "{pigcms{$header['pick_address']['lat']}";
            var long = "{pigcms{$header['pick_address']['long']}";
            var city = "{pigcms{$now_area['area_name']}";
//			var leader_id = "{pigcms{$_GET['leader_id']}";
        </script>
		<style>
			#copy-text img{
				width:16px;
				height:16px;
				float: left;
			}
			.footerMenu li{float: none !important;}
            .x_show{
                height: 1;
            }
            @media only screen and (width: 375px) {   
                .x_show{
                    height: 50px;
                    width: 100%;
                }
                .footerMenu{
                    padding-bottom: 10px;
                }
            }
		</style>
	</head>
	<body>

		<div class="pageBg"></div>

		<div id='headerFixed'></div>
		
		<scrollView id="pageIndex" class="page pageIndex y hide" data-loadurl="{pigcms{:U('index',array('leader_id'=>$_GET['leader_id']))}" data-pagetitle="{pigcms{$config['village_group_alias']}">
			<div class="container">
				<div class="hidden" id='headerBox'>
					<include file="header_fixed"/>
				</div> 
				<!-- Swiper -->
				<if condition="$village_group_index">
					<div id="categoryBox">
						<div class="swiper-container">
							<div class="swiper-wrapper">
								<volist name="village_group_index" id="vo">
									<div class="swiper-slide link newWin" data-url="{pigcms{$vo.url}"><img src="{pigcms{$vo.pic}"></div>
								</volist>
							</div>
							<!-- Add Pagination -->
							<div class="swiper-pagination"></div>
						</div>
					</div>
				</if>
				
				<div class="goodsBox minirefresh-scroll" id="goodsBox"></div> 
				<div class="change-type">
					<div class="arc"><img src="{pigcms{$static_path}villagegroup/images/arc.jpg"></div>
					<div class="align_center color_9 font_size13 text">切换下一分类</div>
				</div> 
                
                 <div class="x_show"></div>

				<!-- 分享图标样式 -->
				<div class="shareBox hidden">
					<img src="{pigcms{$static_path}villagegroup/images/share.png">
					<!-- <span class="text">分享</span> -->
				</div>

				<textarea id="hideShareTxt" style="position: absolute;left: -10000px;opacity: 0;" readonly="">{pigcms{$share_txt}</textarea>
				<div class="hidden shareMask">
					<div class="mask"></div>
					
					<div class="shareContent flex flex-align">
						<div class="share-wrapper">
							<div class="bg-f rel desc-box">
								<div class="title">一键复制拼团信息</div>
								<div class="txtBox">
									<div id="copy-text">{pigcms{$share_txt_img|html_entity_decode}</div>
								</div>
								<button class="btn copyBtn" style="<if condition='$config["mobile_head_color"]'>background: {pigcms{$config["mobile_head_color"]}; </if>" data-clipboard-action="copy" data-clipboard-target="#hideShareTxt">点击复制文字(分享到微信群、QQ群)</button>
								<i class="icon iconfont cancel">&#xe679;</i>
							</div>
							<div class="btn-box"><button class="btn link newWin moreImgsShare" data-url="{pigcms{:U('imgBoxShare',array('leader_id'=>$_GET['leader_id']))}"><img src="{pigcms{$static_path}villagegroup/images/share-icon.png">去复制分享图片</button></div>
						</div>
					</div>
					
					
				</div>
				

			</div>
		</scrollView>
		<scrollView id="pageCart" class="page pageCart y hide" data-loadurl="{pigcms{:U('cart',array('leader_id'=>$_GET['leader_id']))}" data-pagetitle="购物车" data-pagebg="{pigcms{$config.mobile_head_color}"></scrollView>
		<scrollView id="pageMy" class="page pageMy y hide" data-loadurl="{pigcms{:U('my',array('leader_id'=>$_GET['leader_id']))}" data-pagetitle="个人中心"></scrollView>
		 
		<include file="index_footer"/> 
		
        {pigcms{$coupon_html}
	</body>
</html>