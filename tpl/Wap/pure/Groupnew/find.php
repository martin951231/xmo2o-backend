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
		<script type="text/javascript">var nowPage = 'pageFind';</script>
	</head>
	<body>
		<div class="pageBg"></div>
		<scrollView id="pageIndex" class="page pageIndex y hide" data-loadurl="{pigcms{:U('index')}" data-pagetitle="{pigcms{$config.group_index_name|default='同城优选'}"></scrollView>
		<scrollView class="page pageFind y hide" data-loadurl="{pigcms{:U('find')}" data-pagetitle="为您挑好店" data-pagebg="{pigcms{$config.mobile_head_color}">
			<div class="container" style="background-color:{pigcms{$config.mobile_head_color};">
                <if condition="$config.group_find_image neq ''">
                    <div class="adverBoxFind" style="background-image:url('{pigcms{$config.group_find_image}');"></div>
                </if>
				<if condition="$is_weixin_browser">
					<div class="newGroupTipFind">
						<div class="fl"><i class="icon iconfont">&#xe60d;</i><span>产品上新，设置提醒，优惠不错过</span></div>
						<div class="fr" id="setting_inviteWechat">去设置</div>
					</div>
				</if>
                <!--发现里面推荐的具体团购-->
                <if condition="COUNT($find_list['group_list']) gt 0">
                    <div class="goodListFind">
                        <volist name="find_list['group_list']" id="item">
                            <div class="item link newWin" data-url="{pigcms{:U('detail',array('group_id'=>$item['group_id'], 'page_num' => 1))}">
                                <if condition="$item['avatar_info']">
                                    <div class="hitsBox">
                                        <i class="iconfont fl">&#xe60f;</i>
                                        <span class="word fl">浏览</span>
                                        <span class="num fl">{pigcms{$item['look_num']}</span>
                                        <span class="users fr">
                                        <volist name="item['avatar_info']" id="v">
                                            <img src="{pigcms{$v['avatar']}"/>
                                        </volist>
							        </span>
                                    </div>
                                </if>
                                <div class="image" style="background-image:url({pigcms{$item['list_pic']});"></div>
                                <div class="txt">
                                    <div class="name">{pigcms{$item.merchant_name} | {pigcms{$item.s_name}</div>
                                    <div class="price">
                                        <span class="rmb">{pigcms{$config['Currency_symbol']}</span>
                                        <span class="number">{pigcms{$item.price}</span>
                                        <span class="old">市场价： {pigcms{$item.old_price}</span>
                                    </div>
                                </div>
                            </div>
                        </volist>
                    </div>
                </if>
			</div>
		</scrollView>
		<scrollView id="pageMy" class="page pageMy y hide" data-loadurl="{pigcms{:U('my')}" data-pagetitle="个人中心"></scrollView>
		<include file="index_footer"/>
	</body>
</html>