<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$now_village.village_name}</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}weui/weui.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/city_car.css?216"/>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/idangerous.swiper.min.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
	</head>
<body>
	<div class="banner">
		<img src="{pigcms{$static_path}images/ncov_2019/banner.png" style="width:100%;"/>
	</div>
    <div class="page__hd add_hd" style="padding:15px;">
        <h1 class="page__title">小区服务</h1>
    </div>
	<div class="weui-grids" style="background:white;">
        <a href="{pigcms{:U('nCoV_2019_checkin', ['village_id' => $now_village['village_id']])}" class="weui-grid">
            <div class="weui-grid__icon">
                <img src="{pigcms{$static_path}images/ncov_2019/checkin.png" alt=""/>
            </div>
            <p class="weui-grid__label">出入登记</p>
        </a>
		<a href="{pigcms{:U('nCoV_2019_health', ['village_id' => $now_village['village_id']])}" class="weui-grid">
            <div class="weui-grid__icon">
                <img src="{pigcms{$static_path}images/ncov_2019/health.png" alt=""/>
            </div>
            <p class="weui-grid__label">健康打卡</p>
        </a>
		<a href="{pigcms{:U('nCoV_2019_back', ['village_id' => $now_village['village_id']])}" class="weui-grid">
            <div class="weui-grid__icon">
                <img src="{pigcms{$static_path}images/ncov_2019/dengji.png" alt=""/>
            </div>
            <p class="weui-grid__label">返程登记</p>
        </a>
		<a href="{pigcms{:U('village', ['village_id' => $now_village['village_id']])}" class="weui-grid">
            <div class="weui-grid__icon">
                <img src="{pigcms{$static_path}images/ncov_2019/house.png" alt=""/>
            </div>
            <p class="weui-grid__label">智慧小区</p>
        </a>
		<a href="https://m.haodf.com/ndynamic/coronalactivity/activity?businesstype=souyisou" class="weui-grid">
            <div class="weui-grid__icon">
                <img src="{pigcms{$static_path}images/ncov_2019/hospital.png" alt=""/>
            </div>
            <p class="weui-grid__label">免费义诊</p>
        </a>
		<a href="https://news.qq.com/zt2020/page/feiyan.htm" class="weui-grid">
            <div class="weui-grid__icon">
                <img src="{pigcms{$static_path}images/ncov_2019/realtime.png" alt=""/>
            </div>
            <p class="weui-grid__label">实时疫情</p>
        </a>
		<a href="https://ask.dxy.com/ama/index#/disease/24677/info/0" class="weui-grid min_height_hide">
            <div class="weui-grid__icon">
                <img src="{pigcms{$static_path}images/ncov_2019/wenda.png" alt=""/>
            </div>
            <p class="weui-grid__label">疫情知识</p>
        </a>
		<a href="https://xw.qq.com/m/antip" class="weui-grid min_height_hide">
            <div class="weui-grid__icon">
                <img src="{pigcms{$static_path}images/ncov_2019/yiyuanzixun.png" alt=""/>
            </div>
            <p class="weui-grid__label">疫情资讯</p>
        </a>
		<a href="https://xw.qq.com/act/fytrace?pgv_ref=txxwapp&ADTAG=txxwapp" class="weui-grid">
            <div class="weui-grid__icon">
                <img src="{pigcms{$static_path}images/ncov_2019/guiji.png" alt=""/>
            </div>
            <p class="weui-grid__label">病患轨迹</p>
        </a>
    </div>
	<if condition="$config['shop_alias_name']">
		<div class="page__hd add_hd" style="padding:15px;">
			<h1 class="page__title">宅在家</h1>
		</div>
		<div class="page__bd">
			<div class="weui-panel">
				<div class="weui-panel__bd" style="border-top:1px solid #E5E5E5;">
					<div class="weui-media-box weui-media-box_small-appmsg">
						<div class="weui-cells" style="font-size:14px;">
							<a class="weui-cell weui-cell_active weui-cell_access weui-cell_example" href="{pigcms{:U('Shop/index')}">
								<div class="weui-cell__hd"><img src="{pigcms{$static_path}images/ncov_2019/cai.png" alt="" style="width:20px;margin-right:16px;display:block"/></div>
								<div class="weui-cell__bd weui-cell_primary">
									<p>新鲜果蔬送上门</p>
								</div>
								<span class="weui-cell__ft"></span>
							</a>
							<a class="weui-cell weui-cell_active weui-cell_access weui-cell_example" href="{pigcms{:U('Shop/index')}">
								<div class="weui-cell__hd"><img src="{pigcms{$static_path}images/ncov_2019/_waimai.png" alt="" style="width:20px;margin-right:16px;display:block"/></div>
								<div class="weui-cell__bd weui-cell_primary">
									<p>米面粮油及时达</p>
								</div>
								<span class="weui-cell__ft"></span>
							</a>
							<a class="weui-cell weui-cell_active weui-cell_access weui-cell_example" href="{pigcms{:U('Shop/index')}">
								<div class="weui-cell__hd"><img src="{pigcms{$static_path}images/ncov_2019/mian.png" alt="" style="width:20px;margin-right:16px;display:block"/></div>
								<div class="weui-cell__bd weui-cell_primary">
									<p>无接触叫个外卖</p>
								</div>
								<span class="weui-cell__ft"></span>
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</if>
	<div class="weui-footer" style="margin:20px 0;">
		<p class="weui-footer__links">
			<a href="javascript:showQroce();" class="weui-footer__link">由 {pigcms{$config.site_name} 无偿提供</a>
		</p>
		<p class="weui-footer__text">外部链接仅用于大家了解疫情，本站不承担任何责任。</p>
		<if condition="$privacy_policy_url">
			<p class="weui-footer__links">
				<a href="{pigcms{$privacy_policy_url}" class="weui-footer__link" style="color:#999999;font-size:12px;">您的信息受《隐私协议》保护</a>
			</p>
		</if>
	</div>
	<script type="text/javascript">
		function showQroce(){
			layer.open({
				title: '长按识别关注我们'
				,content: '<div class="showQroce"><img src="{pigcms{$config.wechat_qrcode}" style="width:250px;"/></div>'
			});
		}
		$(function(){
			if($('body').height() > $(window).height() + 80){
				$('.min_height_hide').hide();
			}
		});
		
		window.shareData = {  
			"moduleName":"Village",
			"moduleID":"0",
			"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>", 
			"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('nCoV_2019',array('village_id' => $now_village['village_id']))}",
			"tTitle": "在线抗击新冠肺炎【{pigcms{$now_village.village_name}】",
			"tContent": "该功能由{pigcms{$config.site_name}无偿提供"
		};
	</script>
	{pigcms{$shareScript}
</body>
</html>