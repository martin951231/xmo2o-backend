<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
        <if condition="!$is_app_browser">
		<title>{pigcms{$now_village.village_name}</title>
        <else/>
        <title>废品回收</title>
        </if>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?210"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/village.css?211"/>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/jquery.cookie.js?210" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
		<script type="text/javascript">
			var okUrl = "{pigcms{:U('House/village_my_waste_recovery',array('village_id'=>$now_village['village_id']))}";
		</script>
		<!--script type="text/javascript" src="{pigcms{$static_path}js/village_my.js?210" charset="utf-8"></script-->
		<script type="text/javascript" src="{pigcms{$static_path}js/exif.js?210" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/imgUpload.js?210" charset="utf-8"></script>
	</head>
	<body>
    <if condition="!$is_app_browser">
        <header class="pageSliderHide" <if condition='$config["mobile_head_color"]'>style="background-color: {pigcms{$config["mobile_head_color"]};"</if>><div id="backBtn"></div>废品回收</header>
	<else />
		<style type="text/css">
			#container{top:0px}
		</style>
    </if>
		<div id="container">
			<div id="scroller" class="village_repair">
				<form id="repair_form" onsubmit="return false;">
					<section>
						<select name="waste_type" style="width: 100%; height: 40px; border-color: #e5e5e5; color: #737373;">
						  	<option value ="">请选择废品属性</option>
						  	<volist name="waste_type_list" id = "vo">
						  		<option value ="{pigcms{$key}">{pigcms{$vo}</option>
							</volist>
						</select>
						<textarea id="j_cmnt_input" class="newarea" name="waste_detail" placeholder="废品数量或质量"></textarea>
						<textarea id="j_cmnt_input" class="newarea" name="content" placeholder="废品描述"></textarea>
						<div class="pic_tip" id="uploadNum">还可上传<span class="leftNum orange">8</span>张图片，已上传<span class="loadedNum orange">0</span>张(非必填)</div>
						<div class="upload_box"> 
							<ul class="upload_list clearfix" id="upload_list"> 
								<li class="upload_action">
									<img src="{pigcms{$config.site_url}/tpl/Wap/default/static/classify/upimg.png"/>
									<input type="file" accept="image/*" id="fileImage" name=""/>
								</li> 
							</ul> 
						</div>
					</section>
				</form>
				<div class="area_btn"><input type="button" id="submit_btn" value="上报"/></div>
			</div>
		</div>
		<script type="text/javascript" src="{pigcms{$static_path}js/new_village_my.js?210" charset="utf-8"></script>
		{pigcms{$shareScript}
	</body>
</html>