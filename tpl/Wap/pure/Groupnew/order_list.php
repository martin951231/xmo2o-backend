<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{:L_("订单列表")}</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/order_list.css?t={pigcms{:time()}"/>
	</head>
	<body>
		<div class="pageBg"></div>
		<scrollView class="page y order_list" id="order_list_page">
			<div class="container">
				<div class="contentBox">
					<scrollView class="categoryBox cf">
						<div class="item <if condition="!$_GET['type']">active</if>" data-url="{pigcms{:U('order_list',array('page_num'=>$_GET['page_num']))}">{pigcms{:L_("全部")}</div>
						<div class="item <if condition="$_GET['type'] eq 'topay'">active</if>" data-url="{pigcms{:U('order_list',array('type'=>'topay','page_num'=>$_GET['page_num']))}">{pigcms{:L_("待付款")}</div>
						<div class="item <if condition="$_GET['type'] eq 'topin'">active</if>" data-url="{pigcms{:U('order_list',array('type'=>'topin','page_num'=>$_GET['page_num']))}">{pigcms{:L_("待成团")}</div>
						<div class="item <if condition="$_GET['type'] eq 'touse'">active</if>" data-url="{pigcms{:U('order_list',array('type'=>'touse','page_num'=>$_GET['page_num']))}">{pigcms{:L_("待消费/收货")}</div>
						<div class="item <if condition="$_GET['type'] eq 'isover'">active</if>" data-url="{pigcms{:U('order_list',array('type'=>'isover','page_num'=>$_GET['page_num']))}">{pigcms{:L_("已过期")}</div>
						<div class="item <if condition="$_GET['type'] eq 'isuse'">active</if>" data-url="{pigcms{:U('order_list',array('type'=>'isuse','page_num'=>$_GET['page_num']))}">{pigcms{:L_("已消费/收货")}</div>
					</scrollView>
					<div class="orderList" id="orderList">
						<if condition="$order_list['order_list']">
							<volist name="order_list['order_list']" id="item">
								<div class="item">
									<div class="title link newWin" data-url="{pigcms{:U('merchant',array('mer_id'=>$item['mer_id'],'page_num'=>$page_num))}">
										<div class="merLogo fl" style="margin-top: 7px;padding-top: 0px;"><img src="{pigcms{$item.merchant_logo}"/></div>
										<div class="merName fl">{pigcms{$item.merchant_name}</div>
										<div class="status fr">{pigcms{$item.status_info}</div>
									</div>
									<div class="order link newWin" data-url="{pigcms{$item['detail_url']}">
										<div class="groupImg"><img src="{pigcms{$item.list_pic}"/></div>
										<div class="groupNameBox">
											<div class="groupName">{pigcms{$item['group_name']} | {pigcms{$item['s_name']}</div>
											<div class="groupDesc">{pigcms{$item['intro']}</div>
										</div>
										<div class="groupPriceBox">
											<div class="price">{pigcms{$config['Currency_symbol']}{pigcms{$item['price']}</div>
											<div class="old">{pigcms{$config['Currency_symbol']}{pigcms{$item['old_price']}</div>
											<div class="number">X{pigcms{$item['num']}</div>
										</div>
									</div>
									<div class="handleBar cf">
										<if condition="$item['status_key'] eq 4">
											<div class="btn invite fr link newWin" data-url="{pigcms{:U('invitePin',array('order_id'=>$item['order_id'],'page_num'=>$page_num))}">{pigcms{:L_("邀请好友来拼")}</div>
										</if>
										<!-- 需要调整为待消费时展示 电子票券 -->
										<if condition="$item['status_key'] eq 5 && $item['tuan_type'] lt 2">
											<div class="btn invite fr link newWin" data-url="{pigcms{:U('eticket',array('order_id'=>$item['order_id'],'page_num'=>$page_num))}">{pigcms{:L_("电子票券")}</div>
										</if>
										<div class="btn fr link newWin" data-url="{pigcms{:U('order_detail',array('order_id'=>$item['order_id'],'new_group'=>'1','page_num'=>$page_num))}">{pigcms{:L_("订单详情")}</div>
									</div>
								</div>
							</volist>
						<elseif condition="$_GET['page'] elt 1"/>
							<div class="empty" style="margin-top:100px;text-align:center;color:#999;">{pigcms{:L_('暂无订单')}</div>
						</if>
					</div>
				</div>
			</div>
		</scrollView>
		<div id="hideLoad" class="hide"></div>
		
		<div class="gotopBox hide" id="gotopBox">
			<p class="iconfont">&#xe647;</p>
		</div>
		
		<script type="text/javascript">
			window.shareData = {
				"moduleName":"Home",
				"moduleID":"0",
				"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
				"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Groupnew/index')}",
				"tTitle": "{pigcms{$config.group_index_name|default='同城优选'}",
				"tContent": "{pigcms{$config.site_name}"
			};
		</script>
		{pigcms{$shareScript}
		
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		
		<!-- minirefresh -->
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/minirefresh.min.css"/>
		<script type="text/javascript" src="{pigcms{$static_public}minirefresh/minirefresh.min.js" charset="utf-8"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.css"/>
		<script type="text/javascript" src="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.js" charset="utf-8"></script>

		<!-- layer -->
		<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
		
		<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
		
		<script type="text/javascript" src="{pigcms{$static_path}groupnew/js/base.js?t={pigcms{:time()}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}groupnew/js/order_list.js?t={pigcms{:time()}" charset="utf-8"></script>
	</body>
</html>