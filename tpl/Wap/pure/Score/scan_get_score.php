<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
        <title>{pigcms{$scan_content.title}</title>     
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?210"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/village.css?213"/>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/idangerous.swiper.min.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}/layer/layer.m.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
		<script type="text/javascript">
			var location_url = "{pigcms{:U('Systemnews/ajaxList')}";
			var backUrl = "{pigcms{:C('config.site_url')}/wap.php";
			var now_cat_id = "{pigcms{$now_cat_id}";
			var count = "{pigcms{$count}";
		</script>
		<script type="text/javascript" src="{pigcms{$static_path}js/newslist.js?215" charset="utf-8"></script>
		<style>
			.newsListBox dd div{
				font-size:12px;
			}
			.newsListBox dd div{
				margin-right:90px;
				height:21px;
				overflow:hidden;
				word-break:keep-all;
				white-space:nowrap;
				text-overflow:ellipsis;
			}
		</style>
	</head>
	<body> 
		<if condition="!$is_app_browser">
	        <header class="pageSliderHide">{pigcms{$scan_content.title}</header>
	    </if>
		<div id="container" style="background-color: #fff;">
			<!-- <div id="scroller">
				<div style="position:absolute;top:40%;left:50%;transform:translate(-50%,-50%);text-align:center;font-size:20px; width: 100%;">
					<div style="color:red;font-size:25px;">恭喜您获得<span >{pigcms{$scan_content.score}</span>个平台{pigcms{$config.score_name}！</div>
					<p style="color: #5f5f5f;">3秒后自动跳转下一页</p>
		    	</div>
               
			</div> -->
		</div>
	    <script>

			layer.open({
				type: 4,
				content: '<div style="text-align:center;"><div style="color:red;font-size:20px;">恭喜您获得<span >{pigcms{$scan_content.score}</span>个平台{pigcms{$config.score_name}！</div>\
					<p style="color: #5f5f5f; margin-top:3px;">3秒后自动跳转下一页</p></div>',
				closeBtn:0,
				area: ['500px', '350px']
			});
	    	setTimeout(function(){
	    		window.location.href="{pigcms{$scan_content.go_url}"
	    	},3000)
	    </script>
	</body>
</html>