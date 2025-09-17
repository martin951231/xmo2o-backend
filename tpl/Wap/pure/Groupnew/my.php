<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$config.group_index_name|default='同城优选'}</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width, viewport-fit=cover"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/index.css?t={pigcms{:time()}"/>
		<script type="text/javascript">var nowPage = 'pageMy';</script>
		<style>
			.container_box{
				width: 100%;
				box-sizing: border-box;
				padding: 0 10px;
				border-radius: 15px;
				overflow: hidden;
			}
			.container_box>.weui-cells{
				margin: 0;
				padding-left:20px;
				box-sizing: border-box;
			}
			.container_box>.weui-cells>.weui-cell{
				padding: 0;
			}
		</style>
	</head>
	<body>
		<div class="pageBg"></div>
		<scrollView id="pageIndex" class="page pageIndex y hide" data-loadurl="{pigcms{:U('index')}" data-pagetitle="{pigcms{$config.group_index_name|default='同城优选'}"></scrollView>
		<scrollView class="page pageFind y hide" data-loadurl="{pigcms{:U('find')}" data-pagetitle="为您挑好店" data-pagebg="{pigcms{$config.mobile_head_color}"></scrollView>
		<scrollView id="pageMy" class="page pageMy y hide" data-loadurl="{pigcms{:U('my')}" data-pagetitle="个人中心">
			<div class="container">
				<div class="headerBoxMy <if condition='!$user_session'>go_login</if>" style="background-color:{pigcms{$config.mobile_head_color}; margin-bottom: 40px;">
					<div class="profile">
						<div class="avatar fl">
							<img src="{pigcms{$user_session.avatar|default=$static_path.'images/new_my/pic-default.png'}"/>
						</div>
						<if condition="$user_session">
							<div class="nickname fl">{pigcms{$user_session.nickname}</div>
						<else/>
							<div class="nickname fl">点击进行登录</div>
						</if>
					</div>
					<i class="header-annimate-element1"></i>
					<i class="header-annimate-element4"></i>
					<i class="header-annimate-element5"></i>
					<i class="header-annimate-element6"></i>
					<i class="header-annimate-element7"></i>
					<i class="header-annimate-element8"></i>
					<i class="header-annimate-element9"></i>
				</div>
				<div class="orderBoxMy" style="border-radius: 5px;height: 50px; margin-top: 20px;">
					<div class="title" style="border: none;">
						<div class="weui-cell link newWin" data-url="{pigcms{$config.site_url}/packapp/plat/pages/my/my_order"  style="line-height: 50px; padding:0 0 0 10px;">我的订单</div>
					</div>
					<!-- <div class="orderBiz cf">
						<div class="item link newWin" data-url="{pigcms{:U('order_list',array('type'=>'topay','page_num'=>1))}">
							<div class="icon iconfont">
								<div class="logo">&#xe68f;<div class="dot one"></div></div>
							</div>
							<div class="txt">待付款</div>			
						</div>
						<div class="item link newWin" data-url="{pigcms{:U('order_list',array('type'=>'topin','page_num'=>1))}">
							<div class="icon iconfont">
								<div class="logo">&#xe6a2;<div class="dot two"></div></div>
							</div>
							<div class="txt">待拼团</div>						
						</div>
						<div class="item link newWin" data-url="{pigcms{:U('order_list',array('type'=>'touse','page_num'=>1))}">
							<div class="icon iconfont">
								<div class="logo">&#xe65e;<div class="dot three"></div></div>
							</div>
							<div class="txt">待消费</div>
						</div>
						<div class="item link newWin" data-url="{pigcms{:U('order_list',array('type'=>'touse','page_num'=>1))}">
							<div class="icon iconfont">
								<div class="logo">&#xe68e;<div class="dot four"></div></div>
							</div>
							<div class="txt">待收货</div>
						</div>
					</div> -->
				</div>
				<div class="container_box">
					<div class="weui-cells">
						<div class="weui-cell link newWin" data-url="{pigcms{:U('My/adress')}">我的收货地址</div>
					</div>
					<div class="weui-cells">
						<if condition="$kefu_url">
							<div class="weui-cell link newWin" data-url="{pigcms{$kefu_url}">联系客服</div>
						</if>
						<div class="weui-cell link newWin" data-url="{pigcms{:U('My/about')}">关于我们</div>	
					</div>
					<if condition="C('config.open_user_spread') eq 1">
					<div class="weui-cells">
						<div class="weui-cell link newWin" data-url="{pigcms{:U('My/my_spread')}">推广返现</div>
					</div>
                    </if>
				</div>
			</div>
		</scrollView>
		<include file="index_footer"/>
	</body>
</html>