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
		<style>
			.weui-media-box_appmsg:after{
				content: " ";
				width: 12px;
				height: 24px;
				-webkit-mask-position: 0 0;
				mask-position: 0 0;
				-webkit-mask-repeat: no-repeat;
				mask-repeat: no-repeat;
				-webkit-mask-size: 100%;
				mask-size: 100%;
				background-color: currentColor;
				color: rgba(0, 0, 0, 0.3);
				-webkit-mask-image: url(data:image/svg+xml,%3Csvg%20width%3D%2212%22%20height%3D%2224%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M2.454%206.58l1.06-1.06%205.78%205.779a.996.996%200%20010%201.413l-5.78%205.779-1.06-1.061%205.425-5.425-5.425-5.424z%22%20fill%3D%22%23B2B2B2%22%20fill-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E);
				mask-image: url(data:image/svg+xml,%3Csvg%20width%3D%2212%22%20height%3D%2224%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M2.454%206.58l1.06-1.06%205.78%205.779a.996.996%200%20010%201.413l-5.78%205.779-1.06-1.061%205.425-5.425-5.425-5.424z%22%20fill%3D%22%23B2B2B2%22%20fill-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E);
				position: absolute;
				top: 50%;
				right: 10px;
				margin-top: -12px;
			}
			.green{color:green;}
			.red{color:red;}
		</style>
	</head>
<body>
	<div class="page__bd">
		<div class="weui-panel weui-panel_access">
            <div class="weui-panel__hd">历史进出记录列表</div>
            <div class="weui-panel__bd">
				<volist name="list" id="vo">
					<a href="{pigcms{:U('nCoV_2019_show', ['id'=>$vo['pigcms_id'], 'village_id'=>$vo['village_id'], 'from'=>'history'])}" class="weui-media-box weui-media-box_appmsg">
						<div class="weui-media-box__bd">
							<h4 class="weui-media-box__title"><if condition="$vo['check_type'] eq 1">进入<else/>离开</if></h4>
							<p class="weui-media-box__desc">
								<if condition="$vo['check_type'] eq 1">
									<if condition='$vo["temperature"] eq -1 || ($vo["temperature"] gt 0 && $vo["temperature"] lt 37.3)'>
										体温：<span class="green">小于 37.3 ℃</span>
									<elseif condition='$vo["temperature"] eq -2 || ($vo["temperature"] gt 0 && $vo["temperature"] egt 37.3)'/>
										体温：<span class="red">大于 37.3 ℃</span>
									<else/>
										体温：<span class="red">{pigcms{$vo.temperature} ℃</span>
									</if>
								</if>
								<br/>
								时间：{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}
							</p>
						</div>
					</a>
                </volist>
            </div>
        </div>
	</div>
</body>
</html>