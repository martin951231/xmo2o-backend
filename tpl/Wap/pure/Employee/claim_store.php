<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>店铺详情</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?210"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/shop.css?210"/>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}employee/js/claim_store.js?212" charset="utf-8"></script>
		<style>
			.shopDetail .dealcard-img{
				height: 64px;
			}
			.shopDetail .dealcard-block-right{
				margin-bottom:15px;
			}
            .imgInfo {
                min-height: 64px;
            }
            .claim_btn {
                background-color: #01C2AF;
                border: none;
                padding: 2px 10px;
                border-radius: 15px;
                color: white;
                float: right;
            }
		</style>
	</head>
	<body>
		<div class="pageSliderHide" style="display:none;"></div>
		<section class="shopDetail">
			<div class="imgInfo">
				<div class="dealcard-img imgbox" data-pics="<volist name="now_store['all_pic']" id="vo">{pigcms{$vo}<if condition="count($now_store['all_pic']) gt $i">,</if></volist>">

                <if condition="$now_store['all_pic']">
                    <img src="{pigcms{$now_store.all_pic.0}" alt="{pigcms{$now_store.name}">
                    <else/>
                    <img src="{pigcms{$static_path}employee/images/claim_store.png" style="margin-left:0px;" alt="{pigcms{$now_store.name}"/>
                </if>
				</div>
				<div class="dealcard-block-right">
					<div class="brand">{pigcms{$now_store.name}</div>
                    <php>
                        if ($now_store['claim_status'] == 1) {
                    </php>
                    <if condition="$now_store['mer_id'] gt 0">
                        <div class="rateInfo">
                            <if condition="$store_score">
                                <div class="starIconBg"><div class="starIcon" style="width:{pigcms{$store_score['score_all']/$store_score['reply_count']*20}%;"></div></div><div class="starText">{pigcms{:number_format($store_score['score_all']/$store_score['reply_count'],1)}</div>
                                <else/>
                                <span style="color:#999">暂无评分</span>
                            </if>
                        </div>
                        <else/>
                        <span style="color:#01C2AF">审核通过</span>
                        <span style="color:#999">待认领</span>
                        <a class="claim_btn" style="color: white;" href="{pigcms{$config['site_url']}/packapp/merchant/reg.html?store_id={pigcms{$now_store['store_id']}">认领</a>
                    </if>
                    <php>} else if($now_store['claim_status'] == 2) {</php>
                    <span style="color:#999">店铺审核中</span>
                    <php>} else if($now_store['claim_status'] == 3) {</php>
                    <span style="color:red;">审核拒绝</span>
                    <span style="color:#999;">{pigcms{$now_store['check_remark']}</span>
                    <php>}</php>
				</div>
			</div>
			<div class="locationInfo">
				<div class="txt navi" style="background-size: 13px 15px;" data-lng="{pigcms{$now_store['long']}" data-lat="{pigcms{$now_store['lat']}" data-name="{pigcms{$now_store['name']}" data-address="{pigcms{$now_store['name']}">{pigcms{$now_store.area_name}{pigcms{$now_store.adress}</div>
				<div class="phone" data-phone="{pigcms{$now_store.phone}"></div>
			</div>
            <if condition="$is_wap">
			<div class="linkInfo link-url" data-url="{pigcms{:U('Index/index',array('token'=>$now_store['mer_id']))}">
				<div class="icon web">网</div>
				<div class="txt">商家微官网</div>
			</div>
            </if>
			<if condition="$now_store['have_shop'] eq 1">
				<php>$nows_shop_store = M('Merchant_store_shop')->where(array('store_id'=>$now_store['store_id']))->find();</php>
				<div class="linkInfo link-url" data-url="{pigcms{:U('Shop/classic_shop')}&shop_id={pigcms{$now_store['store_id']}">
					<div class="icon" style="background:#F72530;">{pigcms{$config.shop_alias_name}</div>
					<div class="txt">{pigcms{$config.shop_alias_name}</div>
				</div>
			</if>
			<if condition="$config['pay_in_store'] && $now_store['claim_store_id'] gt 0 && $now_store['mer_id'] gt 0">
				<div class="linkInfo link-url" data-url="{pigcms{:U('My/pay',array('store_id' => $now_store['claim_store_id']))}">
					<div class="icon fu">付</div>
					<div class="txt">{pigcms{$config.cash_alias_name}</div>
				</div>
			</if>
			<if condition="$now_store['have_meal'] eq 1">
				<div class="linkInfo link-url" data-url="{pigcms{$now_store['wap_url']}">
					<div class="icon">{pigcms{$config.meal_alias_name}</div>
					<div class="txt">{pigcms{$config.meal_alias_name}</div>
				</div>
			</if>
			<if condition="$now_store['vr_link']">
				<div class="linkInfo link-url" data-url="{pigcms{$now_store['vr_link']}">
					<div class="icon" style="padding:0 2px;">VR</div>
					<div class="txt">{pigcms{$now_store.vr_name|default='VR'}</div>
				</div>
			</if>
		</section>
		<if condition="$store_group_list">
			<section class="storeProList introList">
				<div class="titleDiv"><div class="title">本店{pigcms{$config.group_alias_name}</div></div>
				<ul class="goodList">
					<volist name="store_group_list" id="vo">
						<li class="link-url" data-url="{pigcms{$vo.url}" <if condition="$i gt 2">style="display:none;"</if>>
							<php> if($vo['pin_num'] > 0){ </php><div class="pin_style"></div> <php> }  </php>
							<div class="dealcard-img imgbox">
								<img src="{pigcms{$config.site_url}/index.php?c=Image&a=thumb&width=276&height=168&url={pigcms{:urlencode($vo['list_pic'])}" alt="{pigcms{$vo.group_name}"/>
							</div>
							<div class="dealcard-block-right">
								<div class="title">{pigcms{$vo.group_name}</div>
								<div class="price">
									<strong>{pigcms{$vo['price']}</strong><span class="strong-color">{pigcms{$config['Currency_txt']}</span><if condition="$vo['wx_cheap']"><span class="tag">微信再减{pigcms{$vo.wx_cheap}{pigcms{$config['Currency_txt']}</span></if><span class="line-right">已售{pigcms{$vo['sale_count']+$vo['virtual_num']}</span>
								</div>
							</div>
						</li>
					</volist>
					<if condition="count($store_group_list) gt 2"><li class="more">全部展开</li></if>
				</ul>
			</section>
		</if>
		<if condition="$now_appoint">
			<section class="storeProList introList">
				<div class="titleDiv"><div class="title">本店预约</div></div>
				<ul class="goodList">
					<volist name="now_appoint" id="vo">
						<li  class="link-url" data-url="{pigcms{$vo.url}" <if condition="$i gt 2">style="display:none;"</if>>
							<div class="dealcard-img imgbox">
								<img src="{pigcms{$config.site_url}/index.php?c=Image&a=thumb&width=276&height=168&url={pigcms{:urlencode($vo['list_pic'])}" alt="{pigcms{$vo.appoint_name}"/>
							</div>
							<div class="dealcard-block-right">
								<div class="title">{pigcms{$vo.appoint_name}</div>
								<div class="price">
									<if condition='$vo["is_appoint_price"] neq 0'><strong>{pigcms{$vo['appoint_price']}</strong><span class="strong-color">{pigcms{$config['Currency_txt']}</span><else /><strong>面议</strong></if><span class="line-right">已预约{pigcms{$vo['appoint_sum']}</span>
								</div>
							</div>
						</li>
					</volist>
					<if condition="count($now_appoint) gt 2"><li class="more">全部展开</li></if>
				</ul>
			</section>
		</if>
		<script type="text/javascript">
		window.shareData = {
            "moduleName":"Employee",
            "moduleID":"0",
            "imgUrl": "{pigcms{$now_store.all_pic.0}",
            "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Employee/claim_store', array('store_id' => $now_store['store_id']))}",
            "tTitle": "{pigcms{$now_store.name}",
            "tContent": ""
		};
		</script>
		{pigcms{$shareScript}
		<include file="kefu" />
	</body>
</html>