<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8" />
	<title>个人中心</title>
	<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
	<meta name="apple-mobile-web-app-capable" content="yes"/>
	<meta name='apple-touch-fullscreen' content='yes'/>
	<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
	<meta name="format-detection" content="telephone=no"/>
	<meta name="format-detection" content="address=no"/>
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?215"/>
	<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/idangerous.swiper.min.js" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/common.js?211" charset="utf-8"></script>

	<link href="{pigcms{$static_path}css/check.css" rel="stylesheet"/>
	<style>
		.my-account {
			color: #333;
			position: relative;
			display: block;
			width:100%;
			position: relative;
			height:11.5rem;
			background-color:#06c1ae;
		}
		.account-bg{
			position: absolute;
		    top: 0;
		    left: 0;
		    height: 100%;
		    width: 100%;
		    z-index: -1;
	    }
	    .my-account>img {
	        height: 100%;
	        position: absolute;
	        right: 0;
	        top:0;
	        z-index: 0;
	    }
	    .my-account .user-info {
	        z-index: 1;
	        position: absolute;
	        /*top: 1.5em;
	        left: 6em;*/
	        top: 2rem;
	        left: 70px;
	        box-sizing: border-box;
	        padding-left: 1.9em;
	        font-size: 13px;
	        color: #666;
	    }
	    .my-account .uname {
	        font-size: 18px;
	        color: #fff;
	        margin-top: .1em;
	        margin-bottom: .2em;
	    }
		.my-account .umoney {
			color: #fff;
	    	margin-bottom: 0.06em;
	    }
	    .my-account .avatar_box{
	    	position: absolute;
	        top: 2em;
	        left: 1em;
	        width: 5em;
	        height: 5em;
	        z-index:1;
			border-radius:100%;
			border:1px solid #fff;
			-moz-border-radius:100%;
			-webkit-border-radius:100%;
			overflow:hidden;
		}
		.my-account .avater {
			width:100%;
			height:100%;
		}
		.phone{
			float:left;
			z-index:100;
		}
		.data{
			position: absolute;
			top: 2.8em;
			left: 9em;
			padding:2px 10px;
			border:1px solid #fff;
			border-radius:15px;
			color:#fff;
			width:80px;
			text-align:center;
			margin-top:-7px;
			z-index:100;
			margin-left:10px
		}
		.titleImg{
			width:22px;
			height:22px;
			margin-right:10px;
		}
		.words{
			text-align:center;
			color:#5f5f5f;
			font-size:12px;
		}
		.wh25{
			width:25px;
			height:25px;
		}
		.foloow{
			float:left;
			width:25%;
			text-align:center;
			color:#5f5f5f;
			font-size:12px;
		}
		.foloow_b{
			border-left:1px solid #e5e5e5;
		}
		.padd7{
			padding:7px;
		}
		#close_eye img{
			width: 16%;
			height: 16%;
		}
		.set_up div {

			color:#fff;
		}
		.noPhone{
			padding: 2px 8px;
			background: #999 !important;
			width: auto !important;
			color: #fff !important;
			margin-right: 10px;
			border-radius: 49px;
			opacity: 0.8;
			height: 21px;
			margin-top: -3px;
			line-height: 21px;
		}
		.bd_color{font-weight:600;font-size:18px}
		.pd-t7{padding:0px 7px 0px 7px}
		.mask {
		  background:rgba(0, 0, 0, .5);
			z-index:99;
			position:fixed;
			top:0;
			left:0;
			right:0;
			bottom:0;
			height:100%;
		}
		.contact-box .detail {
		background:#edecec;
			width:80%;
			position:absolute;
			top:50%;
			left:50%;
			transform:translate(-50%,-50%);
			padding:40px 30px 10px;
			box-sizing:border-box;
			border-radius:5px;

		}

		.contact-box .detail .item {
		background:#fff;
			margin:0 auto 20px;
			padding:6px 20px;
			font-size:16px;
			color:#06c1ae;
			display:flex;
			align-items:center;
			border-radius:5px;

		}
   .contact-box .detail .item.active {
       color:#999;
      }

		.contact-box .icon-cha {
		position:absolute;
			top:7px;
			right:7px;
			color:rgba(0, 0, 0, .3);

		}
     .contact-box .detail .item {
	     background:#fff;
		 margin:0 auto 20px;
		 padding:6px 40rpx;
		 font-size:16px;
		 color:#06c1ae;
		 display:flex;
		 align-items:center;
		 border-radius:5px;

	 }
		.my-account{
			height: 11.5rem;
		}
		#money.bt{
			border-top-right-radius: 10px;
			border-top-left-radius: 10px;
		}
		#money.b{
			border-radius: 10px;

		}
		.laba{
			border-bottom-right-radius: 10px;
			border-bottom-left-radius: 10px;
			height: 16px;
			margin-bottom: 10px;
			padding: 10px 15px;
			background: #ffffff;
			margin-top: 0.75rem;
			margin-left: 10px;
			margin-right: 10px;
			position:relative
		}

	 .weui-cells:before {
			content: " ";
			position: absolute;
			left: 0;
			right: 0;
			height: 1px;
			color: #e5e5e5;
			z-index: 2;
			top: 0;
			border-top: 1px solid #e5e5e5;
			-webkit-transform-origin: 0 100%;
			transform-origin: 0 100%;
			-webkit-transform: scaleY(.5);
			transform: scaleY(.5);
		}
		.weui-cell:after {
			content: " ";
			position: absolute;
			left: 0;
			right: 0;
			height: 1px;
			color: #e5e5e5;
			z-index: 2;
			bottom: 0;
			border-top: 1px solid #e5e5e5;
			-webkit-transform-origin: 0 100%;
			transform-origin: 0 100%;
			-webkit-transform: scaleY(.5);
			transform: scaleY(.5);
		}
		.rel{position:relative}


		.weui-cell__ft {
			text-align: right;
			color: #999;
			padding-right: 13px;
			position: relative;
		}
		.weui-cell__ft:after {
			content: " ";
			display: inline-block;
			height: 6px;
			width: 6px;
			border-width: 2px 2px 0 0;
			border-color: #c8c8cd;
			border-style: solid;
			-webkit-transform: matrix(.71,.71,-.71,.71,0,0);
			transform: matrix(.71,.71,-.71,.71,0,0);
			position: absolute;
			top: 50%;
			margin-top: -16px;
			right: 4px;
		}
		#my_order_box>div:nth-child(4n+1){
			clear: both;
		}
		.flex{
		    display: -webkit-box; /* 老版本语法: Safari, iOS, Android browser, older WebKit browsers. */
		    display: -moz-box; /* 老版本语法: Firefox (buggy) */
		    display: -ms-flexbox; /* 混合版本语法: IE 10 */
		    display: -webkit-flex; /* 新版本语法: Chrome 21+ */
		    display: flex; /* 新版本语法: Opera 12.1, Firefox 22+ */
		}
		.flex-wrap{
			-webkit-flex-wrap: wrap;
			-moz-flex-wrap: wrap;
			-ms-flex-wrap: wrap;
			-o-flex-wrap: wrap;
			flex-wrap:wrap;
		}

	</style>
</head>
	<body>
	<div  class="my-account" style="<if condition='!empty($scroll_msg)'>height: 12rem;<else/>height: 10.7rem;</if> <if condition='$config["mobile_head_color"]'>background-color: {pigcms{$config["mobile_head_color"]};</if>">
		<div class="account-bg <if condition="$now_user.phone">set_up</if>" >
	<!--<img src="{pigcms{$static_path}images/new_my/my_bg.png" alt="" style="width:100%;height:100%;">-->
	</div>
	<div class="avatar_box <if condition="$now_user.phone">set_up</if>">
	  <img class="avater" src="<if condition="$now_user['avatar']">{pigcms{$now_user.avatar}<else/>{pigcms{$static_path}images/new_my/pic-default.png</if>" alt="{pigcms{$now_user.nickname}头像"/>
	</div>
	<div class="user-info">
	   <p class="uname <if condition="$now_user.phone">set_up</if>">{pigcms{$now_user.nickname}</p>
		<if condition="$now_user.phone lt 1">
			<a href="{pigcms{:U('bind_user')}">
				<p class="noPhone" >未绑定手机号</p>
			</a>
		<else/>
		   <p class="umoney phone <if condition="$now_user.phone">set_up</if>">{pigcms{$now_user.phone}</p>
		</if>
	    <if condition="isset($config['specificfield'])">
	      <a href="{pigcms{:U('inputinfo')}">
	          <p class="data <if condition="$now_user.phone">set_up</if>">完善资料 ></p>
		  </a>
		</if>
	    <if condition="$config['sign_get_score'] gt 0">
	      <php>if($today_sing){</php>
			<a href="javascript:void(0)">
				<p class="data" style="width:50px;color:#fff">已签到</p>
			</a>
	      <php>}else{</php>
	        <a href="<if condition="$config.wap_sign_url neq ''">javascript:check_http('{pigcms{$config.wap_sign_url}')<else />{pigcms{:U('sign')}</if>">
	          <p class="data" style="width:50px;color:#fff">签到</p>
			</a>
	      <php>}</php>
		</if>
		<?php if ($config['open_company_advance_deposit']): ?>
			<?php if ($qiye_bind): ?>
        		<p style="padding-top: 20px; color: #fff;">{pigcms{$qiye_info['name']}</p>
			<?php else: ?>
        		<p style="padding-top: 20px; color: #fff;">未绑定企业</p>
			<?php endif ?>
		<?php endif ?>
        
	</div>
	<div class="set_up" style="position:absolute;width:50px;height:50px;right:0px;top:2.1rem;z-index:100;">
	<img style="width:20px;height:20px;margin:0px 5px 0 15px;" src="{pigcms{$static_path}images/new_my/set_up.png" alt="">
	       <!--<div style="    color: #eee">设置</div>-->
	</div>
<div id="money" class='<if condition="!empty($scroll_msg)">bt<else/>b</if>' style="padding:0 10px 0px 10px;background-color:#fff;left:10px;right:10px;  top: 8.5rem; position: absolute;">
<div style="padding:10px 0px;text-align:center;<if condition="$config['open_score_fenrun']">font-size:14px;<else />font-size:20px;</if>display:-webkit-box;display: -webkit-flex;display:flex;">
<if condition="$config.open_score_fenrun eq 1">
<div id="my_fenrun_wallet" style="width:33%;flex: 1;">{pigcms{$now_user.fenrun_money|floatval}<p class="words">分润钱包</p></div>
</if>
<div style="width:33%;flex: 1;">
<a href="{pigcms{:U('My/my_money')}">
<div id="close_eye" <if condition="($config['twice_verify'] eq 0) OR $_COOKIE['my_wallet_time'] OR $config['twice_verify_wallet'] eq 0">style="display:none;"</if>>
<img src="{pigcms{$static_path}images/new_my/close_eye.png" >
</div>

<div id="wallet_money"  <if condition="$config['twice_verify'] AND empty($_COOKIE['my_wallet_time'])  AND $config['twice_verify_wallet']">style="display:none;"</if>>{pigcms{$now_user.now_money|sprintf='%.2f',###}</div>


<if condition="$now_user.frozen_money gt 0 AND $config.open_frozen_money eq 1 AND $now_user.free_time gt $_SERVER['REQUEST_TIME']">
<p class="words" id="balance_frozen_money"  style="color:red">(冻结：{pigcms{$now_user.frozen_money|floatval})</p>
</if>
<p class="words" id="balance_money">余额</p>
		</a>
		</div>

<?php if ($config['open_company_advance_deposit']): ?>
	<div id="my_score" style="width:33%;flex: 1;">
		<a href="{pigcms{:U('My/qiye_staff_money_list')}">
            {pigcms{$qiye_bind.money|sprintf='%.2f',###}
			<p class="words" id="score">企业预存款</p>
		</a>
	</div>
	<div id="my_level" style="width:33%;flex: 1;">  <a href="{pigcms{:U('My/cards')}">{pigcms{$card_number}张<p class="words">商家会员卡</p></a></div>

<?php else: ?>
	<div id="my_score" style="width:33%;flex: 1;">
		<a href="{pigcms{:U('My/integral')}">
			{pigcms{$now_user.score_count|floatval}
			<p class="words" id="score">{pigcms{$config.score_name}</p>
		</a>
	</div>
	<if condition="$now_user.level eq 0">
	<div id="my_level" style="width:33%;flex: 1;">  <a href="{pigcms{:U('My/levelUpdate')}">VIP{pigcms{$now_user.level}<p class="words">等级</p>	</a></div>
	<else/>
	<div id="my_level" style="width:33%;flex: 1;"><a href="{pigcms{:U('My/levelUpdate')}">{pigcms{$now_user.lname}<p class="words">等级</p>	</a></div>
	</if>
<?php endif ?>

<div style="clear:both;"></div>
		</div>

		</div>
	</div>
	<if condition="!empty($scroll_msg)">
	<div  class="scroll_msg laba weui-cells" >
		<div style="background: url({pigcms{$static_path}images/lbt_03.png) left 1px no-repeat; background-size: 14px; padding-left: 20px;">
			<div class=""  id="scrollText" style="border-left: #cfcfcf 1px solid; padding-left: 8px; font-size: 12px; height: 15px;">
				<marquee  style="line-height: 16px;  white-space: nowrap;    " >
					<volist name="scroll_msg" id="vo">
						<div style="display:inline-block">
									<span style="padding-right:20px;color:#ff2c4d;">
										<a>{pigcms{$vo.content}</a>
									</span>
						</div>
					</volist>
				</marquee>
			</div>
		</div>
	</div>
	<style>
		#scrollText div a{ color: #ff2c4d;}
	</style>
	<link rel="stylesheet" href="{pigcms{$static_public}font-awesome/css/font-awesome.min.css">
	</if>
	<div style='<if condition="!empty($scroll_msg)">margin:0px 10px<else/>margin:2.7rem 10px 0px 10px</if>'>


	<if condition="$config.open_score_fenrun eq 1 ">
	<dl style="padding:0 10px 10px;background-color:#fff;margin-top:10px;    border-radius: 10px">
		<div style="border-bottom:1px solid #e5e5e5;padding-bottom:10px;">
			<img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/yjt.png">
			<div style="padding-top:13px;width:60%;">我的佣金</div>
		</div>
		<dd style="padding:15px 0px 10px 0;text-align:center;display: flex;">
			<a href="{pigcms{:U('Fenrun/user_free_award_list')}" style="text-align: center; color: #5f5f5f; flex: 1; border-right: #f1f1f1 1px solid;">
				<div style="display: inline-block; background: url({pigcms{$static_path}images/grzx_03.png) left center no-repeat; padding-left: 33px; background-size: 22px 24px;">
					<p style="font-size: 20px; color: #ffa400;">{pigcms{$now_user.free_award_money|floatval}</p>
					<p style="font-size: 12px; color: #5f5f5f;">可用佣金</p>
				</div>
			</a>
			<a href="{pigcms{:U('Fenrun/frozen_award_index')}" style="text-align: center; color: #5f5f5f; flex: 1">
				<div style="display: inline-block; background: url({pigcms{$static_path}images/grzx_05.png) left center no-repeat; padding-left: 33px; background-size: 22px 24px;">
					<p style="font-size: 20px; color: #419aff;">{pigcms{$now_user.frozen_award_money|floatval}</p>
					<p style="font-size: 12px; color: #5f5f5f;">冻结佣金</p>
				</div>
			</a>
		</dd>
	</dl>
	</if>
	<dl style="padding:0 0px 10px;background-color:#fff;margin-top:10px;border-radius:10px" id="my_order_dl">
		<div style="padding:0 10px 10px;" class="weui-cell rel">
			<!--<img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/new_my/order.png" />-->
			<div style="padding-top:13px;width:60%;">我的订单</div>
		</div>
		<dd class="flex flex-wrap" style="padding-bottom:15px;color:#00c4ac;text-align:center;font-size:20px;" id="my_order_box">
			<if condition="isset($config['meal_alias_name'])">
				<div style="width:25%;padding-top:10px;" onclick="location.href='{pigcms{:U('My/group_order_list')}'"><img class="wh25" src="/static/images/my_new/tuangou.png" /><p style="text-align:center;color:#5f5f5f;font-size:12px;">{pigcms{$config.group_alias_name}订单</p></div>
			</if>
			<if condition="isset($config['store_open_shop'])">
				<div style="width:25%;padding-top:10px;" onclick="location.href='{pigcms{:U('My/shop_order_list')}'"><img class="wh25" src="/static/images/my_new/kuaidian.png" /><p style="text-align:center;color:#5f5f5f;font-size:12px;">{pigcms{$config.shop_alias_name}订单</p></div>
			</if>
			<if condition="isset($config['store_open_shop'])">
				<div style="width:25%;padding-top:10px;" onclick="location.href='{pigcms{:U('Mall/mall_order_list')}'"><img class="wh25" src="/static/images/my_new/mall.png" /><p style="text-align:center;color:#5f5f5f;font-size:12px;">{pigcms{$config.mall_alias_name}订单</p></div>
			</if>
			<if condition="isset($config['meal_alias_name'])">
				<div style="width:25%;padding-top:10px;" onclick="location.href='{pigcms{:U('My/foodshop_order_list')}'"><img class="wh25" src="/static/images/my_new/canyin.png" /><p style="text-align:center;color:#5f5f5f;font-size:12px;">{pigcms{$config.meal_alias_name}订单</p></div>
			</if>
			<if condition="isset($config['appoint_alias_name'])">
				<div style="width:25%;padding-top:10px;" onclick="location.href='{pigcms{:U('My/appoint_order_list')}'"><img class="wh25" src="/static/images/my_new/yuyue1.png" /><p style="text-align:center;color:#5f5f5f;font-size:12px;">{pigcms{$config.appoint_alias_name}订单</p></div>
			</if>
			<if condition="$config['pay_in_store']">
				<div style="width:25%;padding-top:10px;" onclick="location.href='{pigcms{:U('My/store_order_list')}'"><img class="wh25" src="/static/images/my_new/apply.png" /><p style="text-align:center;color:#5f5f5f;font-size:12px;">到店付订单</p></div>
			</if>
			<if condition="isset($config['gift_alias_name'])">
				<div style="width:25%;padding-top:10px;" onclick="location.href='{pigcms{:U('My/gift_order_list')}'"><img class="wh25" src="{pigcms{$static_path}images/new_my/gift.png" /><p style="text-align:center;color:#5f5f5f;font-size:12px;">{pigcms{$config['gift_alias_name']}订单</p></div>
			</if>
			<if condition="$config['mobile_recharge_APIKey'] && $config['mobile_recharge_openid']">
				<div style="width:25%;padding-top:10px;" onclick="location.href='{pigcms{:U('Third_recharge/mobile_recharge_list')}'"><img class="wh25" src="{pigcms{$static_path}images/phone_ico1.png" /><p style="text-align:center;color:#5f5f5f;font-size:12px;">话费订单</p></div>
			</if>
			<if condition="$config['open_sub_card'] eq 1">
				<div style="width:25%;padding-top:10px;" onclick="location.href='{pigcms{:U('Sub_card/sub_card_list')}'"><img class="wh25" src="{pigcms{$static_path}images/sub_card.png" /><p style="text-align:center;color:#5f5f5f;font-size:12px;">免单订单</p></div>
			</if>
			<if condition="isset($config['wap_home_show_classify'])">
				<div style="width:25%;padding-top:10px;" onclick="location.href='{pigcms{:U('My/classify_order_list')}'"><img class="wh25" src="/static/images/my_new/xinxi.png" /><p style="text-align:center;color:#5f5f5f;font-size:12px;">{pigcms{$config.classify_name}订单</p></div>
			</if>
			<if condition="isset($config['user_is_real_name'])">
				<div style="width:25%;padding-top:10px;" onclick="location.href='{pigcms{:U('Service/need_list')}'"><img class="wh25" src="/static/images/my_new/kuaipai.png" /><p style="text-align:center;color:#5f5f5f;font-size:12px;">服务快派订单</p></div>
			</if>
			<if condition="isset($config['scenic_alias_name'])">
				<div style="float:left;width:25%;padding-top:15px;" onclick="location.href='{pigcms{:U('Scenic_user/ticket_order_list')}'"><img class="wh25" src="/static/images/my_new/scenic.png" /><p style="text-align:center;color:#5f5f5f;font-size:12px;">{pigcms{$config.scenic_alias_name}订单</p></div>
			</if>

	        <if condition="$config.show_merchant_in_personal_center eq 2">
	        	<div style="float:left;width:25%;padding-top:10px;" onclick="location.href='/packapp/merchant/'"><img class="wh25" src="/static/images/my_new/merchant2.png" /><p style="text-align:center;color:#5f5f5f;font-size:12px;">商家中心</p></div>
	        </if>	
		</dd>
		<div style="clear:both;"></div>
	</dl>
	<dl style="padding:0 10px 10px;background-color:#fff;margin-top:10px;    border-radius: 10px">
	<div style="padding-bottom:10px;" class="weui-cell rel">
		<!--<img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/new_my/follow.png" />-->
		<div style="padding-top:13px;width:60%;">收藏关注</div>
	</div>
	<dd style="padding:10px 0px;color:#00c4ac;text-align:center;font-size:20px;">
		<a href="{pigcms{:U('My/follow_merchant')}"><div class="foloow"><span style='color:#3F9CFF;' class="bd_color">{pigcms{$merchant_count}</span><div class="pd-t7">关注商家</div></div></a>
		<a href="{pigcms{:U('My/group_collect')}"><div class="foloow "><span style='color:#FF5E5E' class="bd_color">{pigcms{$group_count}</span><div class="pd-t7">{pigcms{$config.group_alias_name}收藏</div></div></a>
		<a href="{pigcms{:U('My/group_store_collect')}"><div class="foloow "><span style='color:#A378FF' class="bd_color">{pigcms{$foodshop_count}</span><div class="pd-t7">{pigcms{$config.meal_alias_name}收藏</div></div></a>
		<if condition="isset($config['appoint_alias_name'])">
			<a href="{pigcms{:U('My/appoint_collect')}"><div class="foloow " style="width:24%;"><span style='color:#FFBA2A' class="bd_color">{pigcms{$appoint_count}</span><div class="pd-t7">{pigcms{$config.appoint_alias_name}收藏</div></div></a>
		</if>
		<if condition="isset($config['appoint_alias_name'])">
			<a href="{pigcms{:U('My/appoint_collect')}"><div class="foloow " style="width:24%;"><span style='color:#FFBA2A' class="bd_color">{pigcms{$appoint_count}</span><div class="pd-t7">{pigcms{$config.appoint_alias_name}收藏</div></div></a>
		</if>
	</dd>
	<div style="clear:both;"></div>
	</dl>
	<dl style="padding:0 10px 10px;background-color:#fff;margin-top:10px;    border-radius: 10px">
	<div style="padding-bottom:10px;" class="weui-cell rel">
		<!--<img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/new_my/action.png" />-->
		<div style="padding-top:13px;width:60%;">我的卡券</div>
	</div>
	<dd style="padding:10px 0px 0px 0;text-align:center;font-size:20px;">
		<a href="{pigcms{:U('My/card_list',array('coupon_type'=>'system'))}" style="text-align: center; width: 25%; display: inline-block; float: left;">
			<img src="/static/images/my_new/pingtai_quan.png" width=27 height=27>
			<p style="font-size: 14px; color: #5f5f5f;">平台优惠券</p>
			<p style="font-size: 12px; color:#a0a0a0;">共<span style="color: #e94848;">{pigcms{$coupon_number}</span>张</p>
		</a>
		<a href="{pigcms{:U('My/card_list',array('coupon_type'=>'mer'))}" style="text-align: center; width: 25%; display: inline-block; float: left;">
			<img src="/static/images/my_new/shangjia_quan.png" width=27 height=27>
			<p style="font-size: 14px; color: #5f5f5f;">商家优惠券</p>
			<p style="font-size: 12px; color:#a0a0a0;">共<span style="color: #e94848;">{pigcms{$mer_number}</span>张</p>
		</a>

		<?php if (!$config['open_company_advance_deposit']): ?>		
		<a href="{pigcms{:U('My/cards')}" style="text-align: center; width: 25%; display: inline-block; float: left;">
			<img src="/static/images/my_new/card.png" width=27 height=27>
			<p style="font-size: 14px; color: #5f5f5f;">会员卡</p>
			<p style="font-size: 12px; color:#a0a0a0;">共<span style="color: #e94848;">{pigcms{$card_number}</span>张</p>
		</a>
		<?php endif ?>

		<a href="{pigcms{:U('My/join_activity')}" style="text-align: center; width: 25%; display: inline-block; float: left;">
			<img src="/static/images/my_new/add_group.png" width=26 height=26>
			<p style="font-size: 14px; color: #5f5f5f;">参与活动</p>
			<p style="font-size: 12px; color:#a0a0a0;">共<span style="color: #e94848;">{pigcms{$activity_number}</span>张</p>
		</a>

		<div style="clear:both;"></div>
	</dd>
	</dl>

	<dl style="padding:0 10px;background-color:#fff;margin-top:10px;margin-bottom:10px;    border-radius: 10px">
	<div class="my_order">
		<a href="{pigcms{:U('My/my_spread_code')}" <if condition="$config.open_score_fenrun eq 0">style="display:none"</if>>
		<div style="padding-bottom:10px;" class="weui-cell rel frist">
			<img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/new_my/spread.png" />
			<div style="padding-top:13px;width:60%;">推广有奖</div>
			<div class="weui-cell__ft"></div>
		</div>
		</a>
		<if condition="$config.open_distributor eq 1">

		<a href="{pigcms{:U('Distributor_agent/agent')}" >
			<div style="padding-bottom:10px;" class="weui-cell rel frist">
				<img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/new_my/spread.png" />
				<if condition="empty($distributor['agent'])">
					<div style="padding-top:13px;width:110px;float:right;color:#949494;">暂未开通{pigcms{$config.agent_alias_name}</div>
				</if>
				<div style="padding-top:13px;width:60%;">我是{pigcms{$config.agent_alias_name}</div>
				<div class="weui-cell__ft"></div>
			</div>
		</a>
		</if>

		<if condition="$config['house_open'] && $config['house_position'] eq 3">
		<a href="{pigcms{:U('House/village_list')}">
			<div style="padding-bottom:10px;border-bottom:1px solid #e5e5e5;">
				<img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/new_my/village.png?t=1" />
				<div style="padding-top:13px;width:60%;">我的{pigcms{$config.house_name}服务</div>
				<img src="{pigcms{$static_path}images/new_my/tubiao2_11.png" style="float:right;margin-top:-19px;width:10px;"></img>
			</div>
		</a>
		</if>

        <if condition="$config.open_single_spread eq 1">
            <a href="{pigcms{:U('My/distributor_apply')}" >
                <div style="padding-bottom:10px;" class="weui-cell rel frist">
                    <img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/new_my/spread_money.png" />
                    <div style="padding-top:13px;width:60%;">申请成为分销人员</div>
                    <div class="weui-cell__ft"></div>
                </div>
            </a>
        </if>

        <if condition="$config.open_c2b_spread eq 1">
            <a href="{pigcms{:U('UserSpreadMer/index')}" >
                <div style="padding-bottom:10px;" class="weui-cell rel frist">
                    <img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/new_my/spread_money.png" />
                    <div style="padding-top:13px;width:60%;">我推广的商家</div>
                    <div class="weui-cell__ft"></div>
                </div>
            </a>
        </if>

		<a href="{pigcms{:U('My/lottery_shop_list')}" <if condition="$config.open_share_lottery eq 0">style="display:none"</if>>
			<div style="padding-bottom:10px;" class="weui-cell rel frist">
			<img class="titleImg" style="margin-top:10px;float:left;" src="/static/images/my_new/share.png" />
			<div style="padding-top:13px;width:60%;">分享奖励</div>
				<div class="weui-cell__ft"></div>
			</div>
		</a>
        <php>if($config['close_system_spread'] != 1 && !$config['open_single_spread'] && $config['open_user_spread']){</php>
        	<if condition="$config.open_spread_appoint_user neq 1 || $now_user['can_spread'] eq 1">
        		<if condition="$config.open_distributor eq 1 AND empty($distributor['distributor'])">
	            <a href="{pigcms{:U('Distributor_agent/index')}">
	            <else /> 
	            <a href="{pigcms{:U('My/my_spread')}">
	            </if>
	                <div style="padding-bottom:10px;" class="weui-cell rel frist">
	                <img class="titleImg" style="margin-top:10px;float:left;" src="/static/images/my_new/tuiguang.png" />
	                <if condition="$config.open_distributor eq 1 AND empty($distributor['distributor'])">
	                    <div style="padding-top:13px;width:110px;float:right;color:#949494;">暂未开通{pigcms{$config.distributor_alias_name}</div>
	                </if>
	                <div style="padding-top:13px;width:60%;">我的推广</div>
	                <div class="weui-cell__ft"></div>
	                <!--<img src="{pigcms{$static_path}images/new_my/tubiao2_11.png" style="float:right;margin-top:-19px;width:10px;"></img>-->
	                </div>
	            </a>
        	</if>
        <php>}</php>
		<?php if ($config['open_company_advance_deposit']): ?>		
		<a href="{pigcms{:U('Company_campus/searchSchool')}">
			<div style="padding-bottom:10px;border-bottom:1px solid #e5e5e5;">
			<img class="titleImg" style="margin-top:10px;float:left;" src="/static/images/my_new/xiaoyuanqia.png" />
			<div style="padding-top:13px;width:60%;">餐卡充值</div>
				<img src="{pigcms{$static_path}images/new_my/tubiao2_11.png" style="float:right;margin-top:-19px;width:10px;"></img>
			</div>
		</a>
		<?php endif ?>
        <if condition="$config.show_merchant_in_personal_center eq 1">
		<a href="/packapp/merchant/">
			<div style="padding-bottom:10px;border-bottom:1px solid #e5e5e5;">
			<img class="titleImg" style="margin-top:10px;float:left;" src="/static/images/my_new/merchant.png" />
			<div style="padding-top:13px;width:60%;">商家中心</div>
				<img src="{pigcms{$static_path}images/new_my/tubiao2_11.png" style="float:right;margin-top:-19px;width:10px;"></img>
			</div>
		</a>
		</if>

		<a href="{pigcms{:U('SetAccountDeposit/index')}" <if condition="$config.open_account_deposit eq 0">style="display:none"</if>>
			<div style="padding-bottom:10px;" class="weui-cell rel frist">
			<img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/new_my/allinyun.png" />
			<div style="padding-top:13px;width:60%;">存管账户设置</div>
			<div class="weui-cell__ft"></div>
			</div>
		</a>

		<if condition="$kefu['phone'] OR $kefu['url']">
		<a href="javascript:;" class="my_option">
			<div style="padding-bottom:10px;" class="weui-cell rel frist">
				<img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/new_my/allinyun.png" />
				<div style="padding-top:13px;width:60%;">在线客服</div>
				<div class="weui-cell__ft"></div>
			</div>
		</a>

		</if>

		<a href="{pigcms{:U('House/my_village')}" style="display: none;">
		<div style="padding-bottom:10px;" class="weui-cell rel frist">
			<img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/new_my/extension.png" />
			<div style="padding-top:13px;width:60%;">我的{pigcms{$config['house_name']}</div>
			<div class="weui-cell__ft"></div>
			<!--<img src="{pigcms{$static_path}images/new_my/tubiao2_11.png" style="float:right;margin-top:-19px;width:10px;"></img>-->
		</div>
		</a>

		<if condition="$config['wap_home_show_classify']">
		<a href="{pigcms{:U('Classify/myfabu',array('uid'=>$now_user['uid']))}">
			<div style="padding-bottom:10px;" class="weui-cell rel frist">
				<img class="titleImg" style="margin-top:10px;float:left;" src="/static/images/my_new/fabu.png" />
				<div style="padding-top:13px;width:60%;">我的发布</div>
				<div class="weui-cell__ft"></div>
			</div>
		</a>
		</if>


		<if condition="!empty($now_house_worker)">
		<a href="{pigcms{:U('Worker/index')}">
			<div style="padding-bottom:10px;" class="weui-cell rel frist">
				<img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/new_my/order.png" />
				<div style="padding-top:13px;width:60%;">小区<if condition='$now_house_worker["type"] eq 0'>客服<else />维修</if></div>
				<div class="weui-cell__ft"></div>
			</div>
		</a>
		</if>


        <if condition="$config['find_msg']">
            <a href="{pigcms{:U('Discover/my_discover')}">
                <div style="padding-bottom:10px;" class="weui-cell rel frist">
                    <img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/new_my/find.png" />
                    <div style="padding-top:13px;width:60%;">我的发现</div>
                    <div class="weui-cell__ft"></div>
                </div>
            </a>
        </if>
        <if condition="$config['employee_switch'] eq 1 && !$is_employee">
            <a href="{pigcms{:U('Employee/index')}">
                <div style="padding-bottom:10px;" class="weui-cell rel frist">
                    <img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/new_my/employee_tip.png" />
                    <div style="padding-top:13px;width:60%;">入驻为{pigcms{$config['employee_alias']}</div>
                    <div class="weui-cell__ft"></div>
                </div>
            </a>
            <elseif condition="$config['employee_switch'] eq 1 && $is_employee"/>
            <a href="{pigcms{:U('Employee/my_detail')}">
                <div style="padding-bottom:10px;" class="weui-cell rel frist">
                    <img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/new_my/employee_tip.png" />
                    <div style="padding-top:13px;width:60%;">个人入驻信息</div>
                    <div class="weui-cell__ft"></div>
                </div>
            </a>
        </if>
    </div>
	<!-- <if condition="empty($is_manager)">
	<a href="{pigcms{:U('My/activity_apply')}">
		<div style="padding-bottom:10px;" class="weui-cell rel frist">
			<img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/new_my/find.png" />
			<div style="padding-top:13px;width:60%;">活动分类管理员</div>
			<div class="weui-cell__ft"></div>
		</div>
	</a>
	<else/>
	<a href="{pigcms{:U('My/activity_owner',array('id'=>$is_manager['id']))}">
		<div style="padding-bottom:10px; " class="weui-cell rel frist">
			<img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/new_my/find.png" />
			<div style="padding-top:13px;width:60%;">活动分类管理员</div>
			<div class="weui-cell__ft"></div>

		</div>
	</a>
	<a href="{pigcms{:U('Platform/activityInspect')}">
		<div style="padding-bottom:10px; " class="weui-cell rel frist">
			<img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/new_my/find.png" />
			<div style="padding-top:13px;width:60%;">活动审核管理</div>
			<div class="weui-cell__ft"></div>

		</div>
	</a>
	</if> -->
	        <div style="clear:both;"></div>
	 </dl>
	</div>
		<!-- 店铺联系方式弹窗 -->
		<div class='mask contact-box' style="display:none">
			<div class='detail'>
				<volist name="kefu.phone" id="vo">
				<a class='item' href="tel:{pigcms{$vo}">
					<img src="/static/images/my_new/icon_tel.png" style="width:26px;height:26px"/>
					<span style="padding-left:10px">{pigcms{$vo}</span>
				</a>
				</volist>
				<if condition="$kefu['url'] neq ''">
					<a class='item'  href="{pigcms{$kefu.url}" >
						<img src="/static/images/my_new/icon_weixin1.png" style="width:26px;height:26px"/>
						<span style="padding-left:10px">在线客服</span>
					</a>
				<else/>
					<a class='item'  href="javascript:;" >
						<img src="/static/images/my_new/icon_weixin.png" style="width:26px;height:26px"/>
						<span style="padding-left:10px;color:#999" >暂未开通在线客服</span>
					</a>
				</if>
				<img src="/static/images/my_new/icon_del.png" style="width:26px;height:26px;cursor:pointer" class="icon-cha" />
			</div>
		</div>
	<include file="Public:footer"/>

	<script src="{pigcms{$static_path}js/common_wap.js"></script>
	<script>
	var site_url = '{pigcms{$config.site_url}';
	var jump_url ;
	var phone="{pigcms{$user_session['phone']}";
	$("#verify_phone").html(phone.substring(0,3)+"****"+phone.substring(8,11));
	if($.cookie('my_wallet_time')){
		var in_time = true;
		$('#close_eye').css('display','none');
		$('#wallet_money').css('display','block');
	}else{
		var in_time = false;
	}
	setInterval(function(){
		if(!$.cookie('my_wallet_time')&&twice_verify_wallet){

			$('#close_eye').css('display','block');
			$('#wallet_money').css('display','none');
		}
	},100);
    for(var i=$(".my_order>a").size()-1;i>=0;i--){
	     if($(".my_order>a").eq(i).css("display")!='none'){
			 $(".my_order>a").eq(i).find('.frist').removeClass("weui-cell");
			 break
	     }
	}
	$('.set_up').on('click',function(){
		location.href =	"{pigcms{:U('myinfo')}";
	});

	$(".my_option").on('click',function(){
		$(".contact-box").show()
	})
	$(".icon-cha").on('click',function(){
	  $(".contact-box").hide()
	})
	<if condition="$config.open_score_fenrun eq 0">
		$('#my_wallet').on('click',function(){
			bio_verify({location:"{pigcms{:U('My/my_money')}",twice:twice_verify,hide:'',visible:'',submit:'',cookie:1});
		});
	<else />
	/*$('#my_score').on('click',function(){
		bio_verify({location:"{pigcms{:U('My/integral')}",twice:twice_verify,hide:'',visible:'',submit:'',cookie:1});
	});*/
	/*$('#wallet_money').on('click',function(){
		bio_verify({location:"{pigcms{:U('My/money_list')}",twice:twice_verify,hide:'',visible:'',submit:'',cookie:1});
	});*/
	$('#my_fenrun_wallet').on('click',function(){
		bio_verify({location:"{pigcms{:U('Fenrun/fenrun_money_list')}",twice:twice_verify,hide:'',visible:'',submit:'',cookie:1});
	});
	$('#my_level').on('click',function(){
		bio_verify({location:"{pigcms{:U('My/levelUpdate')}",twice:twice_verify,hide:'',visible:'',submit:'',cookie:1});
	});

	</if>
	$('#close_eye').click(function(){
		if(twice_verify_wallet){
			bio_verify({location:"",twice:twice_verify,hide:'#close_eye',visible:'#wallet_money',submit:'',cookie:1});
		}else{
			$('#close_eye').css('display','none');
			$('#wallet_money').css('display','block');
		}
	});

	// $('#wallet_money').click(function(){
	// if(in_time){
	// location.href =	"{pigcms{:U('My/my_money')}";
	// }else{
	// $('#close_eye').css('display','block');
	// $('#wallet_money').css('display','none');
	// bio_verify({location:"",twice:twice_verify,hide:'#close_eye',visible:'#wallet_money',submit:'',cookie:1});
	// }
	// });
	<if condition="$config['twice_verify']">var twice_verify = true;<else />var twice_verify = false;</if>
	<if condition="$config['twice_verify_wallet']">var twice_verify_wallet = true;<else />var twice_verify_wallet = false;</if>
	<if condition="$_SESSION['user']['verify_end_time']">var verify_end_time = {pigcms{$_SESSION['user']['verify_end_time']};</if>

	//$('.scroll_msg').show();

	function check_http(url){
		url = url.substr(0,4).toLowerCase() == "http" ? url : "http://" + url;
		window.location.href= url;
	}

	<if condition="$config['single_system']">
		if($('#my_order_box div').size() == 1){
			$('#my_order_dl').remove();
		}
	</if>
	</script>
			<!--<script src="{pigcms{$static_path}js/bioAuth.js"></script>-->
	<script type="text/javascript">
	window.shareData = {
		"moduleName":"Home",
		"moduleID":"0",
		"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
		"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Home/index')}",
		"tTitle": "{pigcms{$config.site_name}",
		"tContent": "{pigcms{$config.seo_description}"
	};
	</script>
	{pigcms{$BioAuthticMethod}
	{pigcms{$shareScript}
	</body>
</html>