<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
		<meta http-equiv="Expires" content="-1">
		<meta http-equiv="Cache-Control" content="no-cache">
		<meta http-equiv="Pragma" content="no-cache">
		<meta charset="utf-8">
		<title>{pigcms{$title}</title>
		<link href="{pigcms{$static_path}css/style.css" rel="stylesheet"/>
		<link href="{pigcms{$static_path}css/datepicker.css" rel="stylesheet"/>
		<script src="{pigcms{$static_path}js/jquery-1.8.3.min.js"></script>
		<script src="{pigcms{$static_path}js/jquery.SuperSlide.2.1.1.js"></script>
		<script src="{pigcms{$static_path}js/zlDate.js"></script>
		<style type="text/css">
			.link-nav-i a:hover{
				background: #d92028;
			    display: inline-block;
			    width: 150px;
			    float: left;
			    text-align: center;
			    font-size: 16px;
			    color: #666666;
			}
		</style>
		<!--[if lte IE 9]>
		<script src="{pigcms{$static_path}js/html5shiv.min.js"></script>
		<![endif]-->
	</head>
    <body>
	    <!--顶部登陆-->
		<section class="indexnav">
			<div class="w1200 clr">
				<div class="fl">
					<if condition="!$_SESSION['user']">
						<span class="slogan">您好，欢迎访问{pigcms{$config['site_name']}</span>
						<a href="javascript:void(0)" class="navlogon">登录</a>
						<a href="javascript:void(0)" class="navregister">【免费注册】</a>
					<else/>
						<!-- 登陆后 -->
						<a href="{pigcms{:U('Scenic_user/index')}"><span class="hello">您好，<i>{pigcms{$_SESSION['user']['nickname']}</i></span></a>
						<a id="only_quit" href="javascript:void(0)" class="switch">[退出]</a>
					</if>
				</div>
				<div class="fr indexnav_right clr">
					<ul>
						<!--<li class="navorder">
							<a href="{pigcms{:U('Scenic_user/ticket_order_list')}">
								<span>我的消息</span>
							</a>
						</li>-->
						<li class="navnews">
							<span>我的订单</span>
							<if condition="!$_SESSION['user']">
								<div class="newsdis">
									<a href="javascript:void(0)" id="all_order">全部订单</a>
									<a href="javascript:void(0)" id="my_trip">我的行程</a>
									<a href="javascript:void(0)" id="my_collection">我的收藏</a>
								</div>
							<else/>
								<div class="newsdis">
									<a href="{pigcms{:U('Scenic_user/ticket_order_list')}">全部订单</a>
									<a href="{pigcms{:U('Scenic_user/manager_travel')}">我的行程</a>
									<a href="{pigcms{:U('Scenic_user/collection')}">我的收藏</a>
								</div>
							</if>
						</li>
						<li class="navchat">
							<span>微信版</span>
							<div class="echat clr">
								<div class="img fl">
									<img src="{pigcms{$config['wechat_qrcode']}" width=93 height=93>
								</div>
								<div class="text">
									<h2>关注{pigcms{$config['wechat_name']}</h2>
									<p>预订服务、更多优惠、更便捷</p>
								</div>
							</div>
						</li>
					</ul>
				</div>
			</div>
		</section>