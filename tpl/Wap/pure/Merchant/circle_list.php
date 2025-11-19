<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$info.name}列表</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?222">
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/list.css?210"/>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
		<script type="text/javascript">
			var location_url = "{pigcms{:U('Merchant/circle_list',array('circle_category_id'=>$info['id']))}";
			<if condition="$long_lat">var user_long = "{pigcms{$long_lat.long}",user_lat = "{pigcms{$long_lat.lat}";<else/>var user_long = '0',user_lat  = '0';</if>
		</script>
		<script type="text/javascript" src="{pigcms{$static_path}js/dropdown.js?210" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/circle_list.js?210" charset="utf-8"></script>
		<style type="text/css">
			.listBox dl dd{
				overflow:auto;
				padding:10px;

			}
			.listBox dd p{	
				display: flex;
				align-items: center;
				justify-content: space-between;
			}
			.listBox dd p:first-child{
				margin-bottom:20px;
			}
			.listBox dd p span.circle_name{
				font-size:16px;
				font-weight: bold;
				margin-right: 10px;
			}
			.listBox dd p span.dis{
				color: #43C589;
			}
			.listBox dd p.store_box{color:#666;}
			.listBox dd p span.go_crow{
				width: 10px;
			    height: 10px;
			    border-color: #aaa;
			    border-width: 1px 1px 0 0;
			    border-style: solid;
			    box-sizing: border-box;
			    transform: rotate(45deg);
			    margin-right: 3px;				
			}
		</style>
	</head>
	<body>
		<div id="container">
			<div id="scroller">
				<div id="pullDown">
					<span class="pullDownIcon"></span><span class="pullDownLabel">下拉可以刷新</span>
				</div>


				<script id="groupListBoxTpl" type="text/html">
				{{# if(d.list.length>0){ }}
					{{# for(var i = 0, len = d.list.length; i < len; i++){ }}
						<dd class='link-url' data-url="{{ d.list[i].url }}">
							<p>
								<span class='circle_name'>{{ d['list'][i]['area_name'] }}</span>
								<span class='dis'>{{ d['list'][i]['range_txt'] }}</span>
							</p>
							<p class="store_box">
								<span>共有{{ d['list'][i]['store_num'] }}个店铺</span>
								<span class='go_crow'></span>
							</p>
						</dd>
					{{# } }}
				{{# } }}
				</script>
				<section class="storeListBox listBox">
					<dl>						
					</dl>
					<div class="shade hide"></div>
					<div class="no-deals hide">暂无商圈</div>
				</section>
				<div id="pullUp">
					<span class="pullUpIcon"></span><span class="pullUpLabel">上拉加载更多</span>
				</div>
			</div>
		</div>
		<script type="text/javascript">noAnimate=true;</script>
		<script type="text/javascript">
		window.shareData = {
					"moduleName":"Merchant",
					"moduleID":"0",
					"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
					"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Merchant/store_list')}",
					"tTitle": "{pigcms{$config.store_alias_name}列表",
					"tContent": "{pigcms{$config.site_name}"
		};
		</script>
		{pigcms{$shareScript}
	</body>
</html>