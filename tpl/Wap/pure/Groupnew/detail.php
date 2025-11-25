<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>商品详情</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/detail.css?t={pigcms{:time()}"/>
		<script type="text/javascript">
			var page_index = "{pigcms{:($config['site_url'] . D('House_village')->base_url . 'pages/group/index/home')}";
			var getShareUrl = "{pigcms{:U('share',array('group_id'=>$now_group['group_id']))}";
			var getShareDataUrl = "{pigcms{:U('group_record',array('group_id'=>$now_group['group_id']))}";
			var page_num = {pigcms{$_GET.page_num|intval=###};
			var group_id = "{pigcms{$now_group['group_id']}";
		</script>
        <style>
            .sliderImg {
                background-position: center;
                background-origin: border-box;
            }
            .groupImgBox .sliderImg{cursor: pointer}
            .animated {-webkit-animation-duration:.3s;animation-duration:.3s;-webkit-animation-fill-mode: both;animation-fill-mode: both}
            @keyframes zoomIn { from {opacity: 0;-webkit-transform: scale3d(.3,.3,.3);transform: scale3d(.3,.3,.3) } 50% {opacity: 1 } }
            .zoomIn {-webkit-animation-name: zoomIn; animation-name: zoomIn  }
            @keyframes zoomOut { from {opacity: 1 }  50% {opacity: 0;-webkit-transform: scale3d(.3,.3,.3);transform: scale3d(.3,.3,.3) } to {opacity: 0 } }
            .zoomOut { -webkit-animation-name: zoomOut; animation-name: zoomOut}

            div.global-lightbox {
                display: none;
                position: fixed;
                left: 0;
                right: 0;
                top: 0;
                bottom: 0;
                background: rgba(0,0,0,.95);
                z-index: 1006;
            }
            div.global-lightbox .swiper-slide {
                width: 100%;
                height: 100%;
                position: relative;
                overflow: auto;
                display: -webkit-box;
                display: -webkit-flex;
                display: flex;
                -webkit-box-pack: center;
                -webkit-justify-content: center;
                justify-content: center;
                -webkit-box-align: center;
                -webkit-align-items: center;
                align-items: center;
                -webkit-flex-shrink: 0;
                flex-shrink: 0;
                box-sizing: border-box;
            }
            div.global-lightbox .swiper-slide img {
                max-width: 100%;
                max-height: 100%;
                width: auto;
                height: auto;
            }
            .detail_mask{
                position: fixed;
                top: 0px;
                left: 0px;
                background: rgba(0,0,0,0.7);
                z-index: 1088;
                width: 100%;
                height: 100%;
                display: none;
            }
            .guide {
                position: absolute;
                z-index: 1099;
                width: 100%;
                display: none;
            }
            .sale_out_goods {
                background-color: #999999 !important;
            }
            .combine{
                height: calc(100% + 54px);
            }
            .group-combine-enter {
                padding: 4px 0;
                margin: 13px 0;
                min-height: 36px;
                background: #FFF7EC;
                border: 1px solid #FEECD8;
                border-radius: 12px;
                box-sizing: border-box;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .starIcon {
                width: 14px;
                height: auto;
            }
            .group-combine-txt {
                margin-left: 2px;
                font-weight: 500;
                color: #F54030;
                font-size: 0;
            }
            .group-combine-txt span:first-child {
                font-size: 12px;
                line-height: 18px;
            }
            .view-more {
                color: #F54030;
                font-size: 14px;
                line-height: 18px;
            }

            /* 团购优惠组合商品弹框 */
            .cu-modal {
                position: fixed;
                top: 0;
                right: 0;
                bottom: 0;
                left: 0;
                z-index: 1002;
                opacity: 0;
                outline: 0;
                text-align: center;
                -ms-transform: scale(1.185);
                transform: scale(1.185);
                backface-visibility: hidden;
                perspective: 2000px;
                background: rgba(0, 0, 0, 0.6);
                transition: all 0.3s ease-in-out 0s;
                pointer-events: none;
            }
            .cu-modal::before {
                content: "\200B";
                display: inline-block;
                height: 100%;
                vertical-align: middle;
            }
            .cu-modal.show {
                opacity: 1;
                transition-duration: 0.3s;
                -ms-transform: scale(1);
                transform: scale(1);
                overflow-x: hidden;
                overflow-y: auto;
                pointer-events: auto;
            }
            .cu-dialog {
                position: relative;
                display: inline-block;
                vertical-align: middle;
                margin-left: auto;
                margin-right: auto;
                width: 90%;
                max-width: 100%;
                background-color: #f8f8f8;
                border-radius: 15px;
                overflow: hidden;
                box-sizing: border-box;
            }
            .group-combine-close {
                position: absolute;
                top: 10px;
                right: 10px;
                width: 12px;
                height: 12px;
            }
            .group-combine-title-wrap {
                padding: 16px;
                width: 100%;
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                background: url({pigcms{$static_path}images/groupCombineBg.png) 0 0 no-repeat;
                background-size: 100% 100%;
                box-sizing: border-box;
            }
            .group-combine-title {
                padding-right: 20px;
                width: 100%;
                font-size: 18px;
                font-weight: bold;
                color: #333333;
                line-height: 24px;
                box-sizing: border-box;
                word-break: break-all;
                word-wrap: break-word;
                text-align: left;
            }
            .group-combine-price-wrap {
                margin-top: 8px;
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .text-price {
                font-size: 15px;
                color: #F53D2F;
            }
            .text-old-price {
                font-size: 13px;
                font-weight: 400;
                text-decoration: line-through;
                color: #999999;
            }
            .group-combine-count {
                padding: 0 8px;
                height: 26px;
                box-sizing: border-box;
                background: #CCF2EE;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 500;
                color: #06C1AE;
                vertical-align: middle;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .group-combine-line {
                margin: 0 4px 1px 4px;
            }
            .group-combine-list-wrap {
                width: 100%;
                background: #ffffff;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
            }
            .group-combine-list {
                flex: 1;
            }
            .group-combine-list {
                max-height: 300px;
                overflow-y: auto;
            }
            .group-combine-item {
                padding: 14px 16px;
                width: 100%;
                box-sizing: border-box;
                display: flex;
                justify-content: space-between;
            }
            .group-combine-img {
                width: 55px;
                height: 55px;
                border-radius: 6px;
                overflow: hidden;
            }
            .group-combine-item-content {
                flex: 1;
                padding-left: 12px;
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                flex-direction: column;
            }
            .group-combine-item-name {
                width: 100%;
                font-size: 14px;
                font-weight: 500;
                color: #333333;
                display: flex;
                justify-content: space-between;
                line-height: 18px;
            }
            .group-combine-name {
                flex: 1;
                word-break: break-all;
                word-wrap: break-word;
            }
            .group-combine-distance {
                margin-left: 10px;
                white-space: nowrap;
                font-size: 12px;
                font-weight: normal;
            }
            .text-group-item-price {
                font-size: 14px;
            }
            .text-group-item-lod-price {
                margin-left: 4px;
                font-size: 12px;
            }
            .margin-top-8 {
                margin-top: 8px;
            }
            .group-combine-btn {
                padding: 20px;
                display: flex;
                align-items: center;
                justify-content: space-around;
                background: #ffffff;
            }
            .group-combine-btn span {   
                width: 110px;        
                height: 40px;
                border: 1px solid #06C1AE;
                border-radius: 40px;
                box-sizing: border-box;
                line-height: 40px;
                text-align: center; 
                font-size: 14px;
                font-weight: 500;
            }
            .color-06C1AE {
                color: #06C1AE;
            }
            .color-fff {
                color: #ffffff;
            }
            .bg-06C1AE {
                background: #06C1AE;
            }
            .text-left {
                text-align: left;
            }
            .topFlex {
                display: flex;
                justify-content: space-between;
            }

            /* 团购套餐详情 */
            .packageWrap, .packageWrap ul {
                list-style: none;
            }
            .packageList {
                padding-left: 10px;
                margin-bottom: 10px;
                width: 100%;
                box-sizing: border-box;
            }
            .packageGoodsTitle {
                position: relative;
                font-size: 16px;
                font-weight: bold;
            }
            .packageGoodsTitle::before {
                content: '•';
                position: absolute;
                left: -8px;
                top: 0;
            }
            .packageGoodsNum {
                font-size: 12px; 
            }
            .packageGoodsList {
                margin-top: 10px;
                display: flex;
                justify-content: space-between;
                font-size: 14px;
                line-height: 18px;
            }
            .packageGoodsName {
                flex: 1;
                white-space: pre-wrap;
                word-break: break-all;
                word-wrap: break-word;
            }
            .packageGoodsPrice {
                margin-left: 10px;
                white-space: nowrap;
            }

            /* 可得养老金 */
            .pension {
                margin-left: 4px;
                padding: 0 7px 0 18px;
                font-size: 9px;
                line-height: 18px;
                display: inline-block;
                background-color: red;
                background-image:url({pigcms{$static_public}/images/oldAgePensionIcon.png);
                background-size: 12px 12px;
                background-repeat: no-repeat;
                background-position: 4px;
                color: #ffffff;
                border-radius: 20px;
                white-space: pre-wrap;
                max-width: 100%;
                word-break: break-word;
                font-weight: normal;
                box-sizing: border-box;
            }
        </style>
	</head>
	<body>
         <div class="detail_mask cursor"></div>
         <div class="guide none"><img src='{pigcms{$static_path}images/guide1.png'/></div>
		<div class="pageBg">
			<div class="top"></div>
			<div class="bottom"></div>
		</div>
		<div class="topFixed hide topFlex" id="topFixed">
			<div class="item groupImgBoxBtn" data-id="groupImgBox">商品图片</div>
			<div class="item active merInfoBoxBtn" data-id="merInfoBox">商家信息</div>
			<div class="item introListBoxBtn" data-id="introListBox">购买须知</div>
            <div class="item groupDetailBoxBtn" data-id="groupDetailBox">商品详情</div>
            <if condition="$_GET['source'] neq 'group_combine'">
                <div class="item otherGroupBoxBtn" data-id="otherGroupBox">猜你喜欢</div>
            </if>
		</div>
		<div class="buyHistoryBox hide">
			<div class="img fl">
				<img src=""/>
			</div>
			<div class="fl nickname">Jaty 分享了这个商品</div>
		</div>
        <if condition="$_GET['source'] neq 'group_combine'">
    		<div class="shareBox hide" id="shareBox" data-share_url="{pigcms{:U('share',array('group_id'=>$now_group['group_id']))}">
    			<p class="iconfont">&#xe66c;</p>
    			<p class="txt">分享</p>
    		</div>
        </if>
		<div class="gotopBox hide" id="gotopBox">
			<p class="iconfont">&#xe647;</p>
		</div>
		<scrollView class="page <if condition="$_GET['source'] eq 'group_combine'">combine</if> y" id="page">
			<div class="container">
				<div class="groupImgBox swiper-container" id="groupImgBox">
					<div class="swiper-wrapper">
                        <if condition="$now_group.all_pic">
                            <volist name="now_group.all_pic" id="item">
                                <div class="swiper-slide">
                                    <div class="sliderImg" style="background-image:url({pigcms{:thumb_img($item['image'], 900, 500, 'fill')})"></div>
                                </div>
                            </volist>
                        </if>
					</div>
					<div class="swiper-pagination"></div>
					<div class="visitNum">{pigcms{$group_record_num['look_num']}人感兴趣</div>
				</div>
				<div class="groupNameBox">
					<div class="groupName">{pigcms{$now_group['merchant_name']} | {pigcms{$now_group['s_name']}</div>
					<div class="groupDesc">{pigcms{$now_group['intro']}</div> <!--特色巴沙鱼锅/菌汤鸡锅/酸汤牛肉锅-->
                    <!-- 下方如果不拼的，将 class里的 pin去掉 -->
					<div class="groupPrice <if condition='$now_group.pin_num gt 1'>pin</if>">
                        <if condition="$now_group.pin_num gt 1">
                            <span class="tip">{pigcms{$now_group.pin_num}人团</span>
                        </if>
						<span class="price">{pigcms{$config['Currency_symbol']}{pigcms{$now_group.price}</span>
						<span class="old">{pigcms{$config['Currency_symbol']}{pigcms{$now_group.old_price}</span>
                        <if condition="$config['open_old_age_pension'] == '1'">
                        <span class="pension">可得养老金 {pigcms{$config['Currency_symbol']}{pigcms{$now_group.old_age_pension}</span>
                        </if>  
					</div>
                    <!-- 团购优惠组合入口 -->
                    <if condition="$groupCombine && $_GET['source'] neq 'group_combine'">
                            <div class="group-combine-enter link" data-url="/packapp/plat/pages/group/groupCombineDetail?combine_id={pigcms{$groupCombine['combine_id']}&group_id={pigcms{$_GET['group_id']}">
                            <img src="{pigcms{$static_path}img/star.png" alt="" class="starIcon">
                            <div class="group-combine-txt">
                                <span>该商品正在参加超值优惠组合活动，点此查看享受</span>
                                <span class="view-more">更多优惠</span>
                            </div>
                        </div>
                    </if>
                    <!--购买人或者浏览者信息-->
                    <if condition="$group_record_num['avatar_info'] && $_GET['source'] neq 'group_combine'">
                        <div class="buyListBox cf">
                            <if condition="$group_record_num['type'] eq 1">
                                <div class="buyNum">{pigcms{$group_record_num['look_num']}人已浏览</div>
                                <div class="avatarList fl">
                                    <span class="item more">...</span>
                                    <volist name="group_record_num.avatar_info" id="v">
                                        <span class="item" style="background-image:url({pigcms{$v['avatar']});"></span>
                                    </volist>
                                </div>
                                <else/>
                                <div class="buyNum">{pigcms{$group_record_num['buy_times']}人已购买</div>
                                <div class="avatarList fl">
                                    <span class="item more">...</span>
                                    <volist name="group_record_num.avatar_info" id="v">
                                        <span class="item" style="background-image:url({pigcms{$v['avatar']});"></span>
                                    </volist>
                                </div>
                            </if>
                            <div class="invite fr" id="inviteBtn" data-share_url="{pigcms{:U('share',array('group_id'=>$now_group['group_id']))}">邀请好友一起抢</div>
                        </div>
                    </if>
				</div>
                <!--正在拼团的展示-->
                <if condition="$can_join && !$user_join">
                    <div class="pinInfoBox" id="pinInfoBox">
                        <div class="title">正在拼的，直接参团成功率更高</div>
                        <div class="pinList">
                            <volist name="can_join" id="vo">
                                <div class="item cf">
                                    <div class="avatar fl">
                                        <img src="<if condition='$vo.avatar neq ""'>{pigcms{$vo.avatar}<else/>{pigcms{$static_public}img/images/nohead.png</if>"/>
                                    </div>
                                    <div class="nickTime fl">
                                        <div class="nickRow">
                                            <span class="nick">{pigcms{$vo.nickname}</span>
                                            <span class="num">还差<span>{pigcms{$vo['complete_num']-$vo['num']}</span>人成团</span>
                                        </div>
                                        <div class="timeRow">
                                            剩余 <span class="time" data-time="{pigcms{$vo.surplus_time}"></span>
                                        </div>
                                    </div>
                                    <div class="buy link fr" data-url="{pigcms{:U('Group/buy',array('group_id'=>$now_group['group_id'],'new_group'=>1,'gid'=>$vo['id'],'share_uid'=>$_GET['share_uid'],'openid'=>$_GET['openid'],'share_openid'=> $_GET['share_openid']))}">{pigcms{$config['Currency_symbol']}{pigcms{$now_group.price}马上抢</div>
                                </div>
                            </volist>
                        </div>
                        <div class="pinTip cf">
                            <div class="fl">人满成团<span></span>人不满退款</div>
                            <div class="fr" id="pinRuleBtn">详细规则</div>
                        </div>
                    </div>
                    <elseif condition="$group_share_info"/>
                    <div class="pinInfoBox" id="pinInfoBox">
                        <div class="title">正在拼的，直接参团成功率更高</div>
                        <div class="pinList">
                            <div class="item cf">
                                <div class="avatar fl">
                                    <img src="<if condition='$group_share_info.avatar neq ""'>{pigcms{$group_share_info.avatar}<else/>{pigcms{$static_public}img/images/nohead.png</if>"/>
                                </div>
                                <div class="nickTime fl">
                                    <div class="nickRow">
                                        <span class="nick">{pigcms{$group_share_info.nickname}</span>
                                        <span class="num">还差<span>{pigcms{$group_share_info['complete_num']-$group_share_info['num']}</span>人成团</span>
                                    </div>
                                    <div class="timeRow">
                                        剩余 <span class="time" data-time="{pigcms{$group_share_info.surplus_time}"></span>
                                    </div>
                                </div>
                                <div class="buy link fr" data-url="{pigcms{:U('Group/buy',array('group_id'=>$now_group['group_id'],'new_group'=>1,'gid'=>$group_share_info['id'],'share_uid'=>$_GET['share_uid'],'openid'=>$_GET['openid'],'share_openid'=> $_GET['share_openid']))}">{pigcms{$config['Currency_symbol']}{pigcms{$now_group.price}马上抢</div>
                            </div>
                        </div>
                        <div class="pinTip cf">
                            <div class="fl">人满成团<span></span>人不满退款</div>
                            <div class="fr" id="pinRuleBtn">详细规则</div>
                        </div>
                    </div>
                </if>
                <!--团购对应商家和其名下店铺-->
				<div class="merInfoBox" id="merInfoBox">
					<div class="merInfo cf link newWin" data-title="{pigcms{$merchant_info['name']}" data-url="{pigcms{:U('merchant',array('mer_id'=>$now_group['mer_id'], 'page_num'=>$_GET['page_num']+1))}">
						<div class="merLogo fl">
							<img src="<if condition='$merchant_info.logo neq ""'>{pigcms{:replace_file_domain($merchant_info['logo'])}<else/>{pigcms{$static_public}img/images/nohead.png</if>"/>
						</div>
						<div class="merName fl">
							<div class="name">{pigcms{$merchant_info['name']}</div>
							<div class="desc">{pigcms{$merchant_info['s_txt']|default=$merchant_info['name']}</div>
						</div>
						<div class="merLink fr">进店逛逛</div>
					</div>

                    <if condition="$now_group['store_list']">
                        <div class="storeList">
                            <volist name="now_group['store_list']" id="vo" key="k">
                                <div class="item <if condition="$k gt 2">hide</if>">
                                    <div class="storeName"><span class="name">{pigcms{$vo.name}</span><if condition="strip_tags($vo['work_time']) neq '-'"><span class="clock">{pigcms{$vo.work_time}</span></if></div>
                                    <div class="storeAddress">{pigcms{$vo.adress}</div>
                                    <div class="phoneNavi">
                                        <div class="phone iconfont fl" data-phone="{pigcms{$vo.phone}">&#xe615;</div>
                                        <div class="navi iconfont fl" data-lng="{pigcms{$vo.long}" data-lat="{pigcms{$vo.lat}" data-name="{pigcms{$vo.name}" data-address="{pigcms{$vo.adress}">&#xe606;</div>
                                    </div>
                                </div>
                            </volist>
                            <if condition="COUNT($now_group['store_list']) gt 2">
                                <div class="more iconfont down">&#xe608;</div>
                                <div class="more iconfont up hide">&#xe607;</div>
                            </if>
                        </div>
                    </if>
				</div>

                <if condition="$now_group['address_detail'] || COUNT($now_group['cue_arr']) gt 0">
                    <div class="introListBox boxBlock" id="introListBox">
                        <div class="centerTitle">Shopping Notes</div>
                        <div class="centerDesc">购买须知</div>
                        <div class="contentBox">
                            <ul>
                                <if condition="$now_group['address_detail']">
                                    <li><b>详细地址：</b>{pigcms{$now_group.address_detail|nl2br=###}

                                        <span class="navi iconfont" data-lng="{pigcms{$now_group.long}" data-lat="{pigcms{$now_group.lat}" data-name="{pigcms{$now_group.address_detail}" data-address="{pigcms{$now_group.address_detail}">&#xe606;</span>
                                    </li>
                                </if>
                                <volist name="now_group['cue_arr']" id="vo">
                                    <if condition="$vo['value']">
                                        <li><b>{pigcms{$vo.key}：</b>{pigcms{$vo.value|nl2br=###}</li>
                                    </if>
                                </volist>
                            </ul>
                        </div>
                    </div>
                </if>
                <if condition="$group_content">
					<php>$group_detail = array_pop($group_content);</php>
					<div class="groupDetailBox boxBlock" id="groupDetailBox">
						<div class="centerTitle">Commodity Details</div>
						<div class="centerDesc">{pigcms{$group_detail.name}</div>
						<div class="contentBox">
							{pigcms{:replace_file_domain_content($group_detail['content'])}
						</div>

                        <!-- 套餐商品 -->
                        <if condition="$package_list">
                            <volist name="package_list" id="vo">
                                <ul class="packageWrap">
                                    <li class="packageList">
                                        <div>
                                            <span class="packageGoodsTitle">{pigcms{$vo.package_name}</span>
                                            <span class="packageGoodsNum">（可选{pigcms{$vo.num}份）</span>
                                        </div>
                                        <if condition="$vo['goods']">
                                            <ul>
                                                <volist name="vo['goods']" id="voo">
                                                    <li class="packageGoodsList">
                                                        <span class="packageGoodsName">{pigcms{$voo.name}</span>
                                                        <span class="packageGoodsPrice">{pigcms{$config['Currency_symbol']} {pigcms{$voo.price}</span>
                                                    </li>
                                                </volist>
                                            </ul>
                                        </if>
                                    </li>
                                </ul>
                            </volist>
                        </if>
					</div>
                </if>

                <if condition="$category_group_list && $_GET['source'] neq 'group_combine'">
                    <div class="otherGroupBox boxBlock goodListBox list" id="otherGroupBox">
                        <div class="centerTitle">More Good Life</div>
                        <div class="centerDesc">更多美好生活</div>
                        <scrollView class="goodList">

                            <volist name="category_group_list" id="vo">
                                <div class="goodItem newWin link" data-url="{pigcms{$vo.detail_url}">
                                    <div class="goodImage pr">
										<if condition="$vo['pin_num'] gt 0">
											<div class="pin_style"></div>
										</if>
                                        <img src="{pigcms{$vo['list_pic']}"/>
                                    </div>
                                    <div class="goodName">{pigcms{$vo.name} | {pigcms{$vo.s_name}</div>
                                    <div class="goodPrice">{pigcms{$config['Currency_symbol']}{pigcms{$vo.price}<span class="unit">/份</span><span class="oldPrice">{pigcms{$config['Currency_symbol']}{pigcms{$vo.old_price}</span></div>
                                </div>
                            </volist>
                        </scrollView>
                    </div>
                </if>
				<div style="height:10px;"></div>
			</div>
		</scrollView>
		<div class="pinRuleBox hide" id="pinRuleBox">
			<div class="pinRuleBg" id="pinRuleBg"></div>
			<div class="pinRuleCon" id="pinRuleCon">
				<div class="title">拼团玩法<div class="close iconfont" id="closePinRule">&#xe60c;</div></div>
				<scrollView class="content y">
					<div class="t">1. 拼团有效期</div>
					<div class="c">此拼团有效期是自开团时刻起24小时内，如果距离活动结束时间小于24小时，则以活动结束时间为准。</div>
					<div class="t">2. 拼团成功</div>
					<div class="c">拼团有效期内，支付的用户达到参团人数，则拼团成功，商家进入发货流程；高峰期同时参加同一个团，则可能会造成一个团多个用户。</div>
                    <div class="t">4. 拼团取消</div>
                    <if condition="$now_group['group_refund_fee'] neq 100">
                        <div class="c">拼团成功前，开团人（团长）在拼团时限内不允许取消订单，参团人允许取消订单。</div>
                        <div class="c">拼团成功后，开团人（团长）不得取消订单，参团人取消订单则收取一定的手续费。</div>
                        <else/>
                        <div class="c">拼团成功前，开团人（团长）在拼团时限内不允许取消订单，参团人不允许取消订单。</div>
                        <div class="c">拼团成功后，开团人（团长）不得取消订单，参团人不允许取消订单。</div>
                    </if>
					<div class="t">5. 拼团失败</div>
					<div class="c">若超出有效期还未达到人数要求，则认定为拼团失败，自动退款至付款来源。</div>
				</scrollView>
			</div>
		</div>
        <div class="webShare hide" id="webShare">
            <div class="webShareBg" id="webShareBg"></div>
            <div class="webShareContent" id="webShareContent">
                <div class="content">
                    <img class="pic" id="poster" src=""/>
                    <div class="desc">长按海报即可保存/分享</div>
                    <img class="close" id="closePoster" src='{pigcms{$static_path}images/close.png'/>
                </div>
            </div>
        </div>
        <php>if ($_GET['source'] != 'group_combine') { </php>
		<footer class="footerMenu">
			<ul>
                <notempty name="kf_url">
                    <li class="tabBtn home">
                        <p class="icon iconfont">&#xe623;</p>
                        <p>首页</p>
                    </li>
                    <!--<li class="tabBtn kefu link newWin" data-url="/wap.php?c=My&a=concact_kefu&mer_id=1">-->
                    <li class="tabBtn kefu link newWin" data-url="{pigcms{$kf_url}">
                        <p class="icon iconfont find">&#xe617;</p>
                        <p>客服</p>
                    </li>
                <else/>
                    <li class="tabBtn home" style="width: 36%">
                        <p class="icon iconfont">&#xe623;</p>
                        <p>首页</p>
                    </li>
                </notempty>

                <php>if ($group_share_info) { </php>
                    <li class="tabBtn link myPin" data-url="{pigcms{:U('Group/buy',array('group_id'=>$now_group['group_id'],'new_group'=>1,'gid'=>$group_share_info['id'],'share_uid'=>$_GET['share_uid'],'openid'=>$_GET['openid'],'share_openid'=> $_GET['share_openid']))}">
                        <p>立即参团</p>
                    </li>
                <php>}else{</php>

                    <!-- 需要判断是不是规格 -->
                    <if condition="!$group_specifications">
                        <php>if (!$now_group['sale_out']) { </php>
                            <li class="tabBtn buy newWin <if condition='$now_group.pin_num gt 1'>hide</if>" id="buyBtnOpt" data-group_id="{pigcms{$_GET['group_id']}" data-combine_id="{pigcms{$groupCombine.combine_id}" data-title="提交订单" data-url="{pigcms{:U('Group/buy',array('group_id'=>$now_group['group_id'],'new_group'=>1,'share_uid'=>$_GET['share_uid'],'openid'=>$_GET['openid'],'share_openid'=> $_GET['share_openid']))}">
                                <p>立即购买</p>
                            </li>
                        <php>}else{</php>
                            <li class="tabBtn buy sale_out_goods <if condition='$now_group.pin_num gt 1'>hide</if>">
                                <p>立即购买</p>
                            </li>
                        <php>}</php>
                    <else/>
                        <php>if (!$now_group['sale_out']) { </php>
                            <li class="tabBtn buy <if condition="$now_group['pin_num'] gt 1 || $now_group['sale_out']">hide</if>"  id="specifications" data-title="提交订单" data-url="{pigcms{:U('Group/buy',array('group_id'=>$now_group['group_id'],'new_group'=>1,'share_uid'=>$_GET['share_uid'],'openid'=>$_GET['openid'],'share_openid'=> $_GET['share_openid']))}">
                                <p>立即购买</p>
                            </li>
                        <php>}else{</php>
                            <li class="tabBtn buy sale_out_goods <if condition='$now_group.pin_num gt 1'>hide</if>">
                                <p>立即购买</p>
                            </li>
                        <php>}</php>
                    </if>
                    <li class="tabBtn link directBuy  <if condition='$now_group.pin_num lt 1 || ($user_join && $is_login)'>hide</if>" data-url="{pigcms{:U('Group/buy',array('group_id'=>$now_group['group_id'],'type'=>1,'new_group'=>1,'share_uid'=>$_GET['share_uid'],'openid'=>$_GET['openid'],'share_openid'=> $_GET['share_openid']))}">
                        <p class="icon">{pigcms{$config['Currency_symbol']}{pigcms{$now_group.old_price}</p>
                        <p>直接购买</p>
                    </li>
                    <li class="tabBtn link pinBuy <if condition='$now_group.pin_num lt 1 || ($user_join && $is_login)'>hide</if>" data-url="{pigcms{:U('Group/buy',array('group_id'=>$now_group['group_id'],'type'=>3,'new_group'=>1,'share_uid'=>$_GET['share_uid'],'openid'=>$_GET['openid'],'share_openid'=> $_GET['share_openid']))}">
                        <p class="icon">{pigcms{$config['Currency_symbol']}{pigcms{$now_group.price}</p>
                        <p>{pigcms{$now_group.pin_num}人成团</p>
                    </li>
                    <li class="tabBtn link myPin <if condition='$now_group.pin_num lt 1 || !$user_join || !$is_login || !$user_join_pin'>hide</if>" data-url="{pigcms{:U('invitePin',array('type'=>'topin','new_group'=>1,'order_id'=>$user_join_pin['order_id']))}">
                        <p>查看我的团</p>
                    </li>

                <php>}</php>
			</ul>
		</footer>

        <php>}</php>
		<div class="specificationsBg"></div>
		<div class="specificationsBox">
			<div class="productBox cf">
				<div class="imgBox">
					<img src="{pigcms{$now_group.all_pic.0.s_image}"/>
				</div>
				<div class="content">
					<div class="s_name">{pigcms{$now_group.s_name}</div>
					<div class="specifications_price">
                        <span class="price">{pigcms{$config['Currency_symbol']}{pigcms{$now_group.price}</span>
                        <span class="old">{pigcms{$config['Currency_symbol']}{pigcms{$vo.old_price}</span>
                    </div>
					<div class="tip"></div>
				</div>
			</div>
			<div class="specifications">
                <volist name="group_specifications" id="item">
                    <div class="item cf" data-id="{pigcms{$item['specifications_id']}" data-price="{pigcms{$config['Currency_symbol']}{pigcms{$item.price}" data-old="{pigcms{$config['Currency_symbol']}{pigcms{$item.old_price}" data-stock="{pigcms{$item['today_can_buy']}" data-num="{pigcms{$item['once_min']}" data-num_desc="{pigcms{$item['num_desc']}">
                        <div class="fl">{pigcms{$item['specifications_name']}</div>
                        <div class="fr iconfont">&#xe600;</div>
                    </div>
                </volist>
			</div>
			<div class="specificationsBuy">立即购买</div>
		</div>

        <!-- 团购优惠组合详情 -->
        <div class="cu-modal" id="groupCombineModal">
            <div class="cu-dialog bg-white" id="groupCombineDialog">
                
            </div>
        </div>

        
		<script type="text/javascript">
            var is_app_browser = <if condition="$is_app_browser">true<else/>false</if>;
            var app_browser_type = "{pigcms{$app_browser_type}";
            var is_google_map = "{pigcms{$config.google_map_ak}";
			var share_wx = "{pigcms{:(($config['pay_wxapp_important'] && $config['pay_wxapp_username']) ? 'wxapp' : 'h5') }";
			var pay_wxapp_username = "{pigcms{$config['pay_wxapp_username']}";
            var get_route = "{pigcms{$config['site_url']}/wap.php?g=Wap&c=Groupnew&a=get_route";
            var location_url = "{pigcms{$config['site_url']}/v20/public/index.php/group/api.GroupCombine/getGroupCombineDetail"
		</script>
		<script type="text/javascript">
			window.shareData = {
				"moduleName":"Home",
				"moduleID":"0",
				"imgUrl": "{pigcms{$now_group.all_pic.0.image}",
				"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('detail',array('group_id'=>$_GET['group_id']))}",
                // "tTitle": "我推荐「 {pigcms{$now_group['merchant_name']} | {pigcms{$now_group['s_name']}」",
                "tTitle": "我推荐{pigcms{$now_group['group_name']}",
				"tContent": "{pigcms{$now_group['intro']}"
			};
		</script>
		{pigcms{$shareScript}

		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/swiper.min.js" charset="utf-8"></script>

		<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>

        <script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
		
        <script type="text/javascript" src="{pigcms{$static_path}js/jquery.cookie.js"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/common.js?214" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}groupnew/js/base.js?t={pigcms{:time()}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}groupnew/js/detail.js?t={pigcms{:time()}" charset="utf-8"></script>
        <script type="text/javascript" charset="utf-8">
			if(is_app_browser){
				if(app_browser_type == 'android'){
					if(iosCommonNew.getVersion() >= 1400){
						var android_shareLifePass = {imgUrl: window.shareData.imgUrl, sendFriendLink:window.shareData.sendFriendLink, tTitle:window.shareData.tTitle, tContent:window.shareData.tContent };
						android_shareLifePass.share_wx = share_wx;
						if(android_shareLifePass.share_wx == 'wxapp'){
							android_shareLifePass.share_wx_info = {userName:"{pigcms{$config['pay_wxapp_username']}", path:'/pages/index/index?redirect=webview&webview_url=' + encodeURIComponent(location.href), image:window.shareData.imgUrl, title:window.shareData.tTitle};
						}
						window.lifepasslogin.shareLifePassNew(JSON.stringify(android_shareLifePass));
					}else{
						window.lifepasslogin.shareLifePass(window.shareData.tTitle,window.shareData.tContent,window.shareData.imgUrl,window.shareData.sendFriendLink);
					}
				} else {
					var ios_shareLifePass = {name:'shareLifePass', imgUrl: window.shareData.imgUrl, sendFriendLink:window.shareData.sendFriendLink, tTitle:window.shareData.tTitle, tContent:window.shareData.tContent };
					ios_shareLifePass.share_wx = share_wx;
					if(ios_shareLifePass.share_wx == 'wxapp' && iosCommonNew.getVersion() >= 1406){
						ios_shareLifePass.share_wx_info = JSON.stringify({userName:"{pigcms{$config['pay_wxapp_username']}", path:'/pages/index/index?redirect=webview&webview_url=' + encodeURIComponent(location.href), image:window.shareData.imgUrl, title:window.shareData.tTitle});
					}
					iosCommonNew.iosFunction('shareLifePass', ios_shareLifePass);
				}
			}
			
            var imgswiper =null, lgtbox = $('#globalLightbox');
            $(document).on('click', '.groupImgBox .sliderImg', function () {
                var that = $(this), imgh = '';
                that.closest(".swiper-wrapper").find('.sliderImg').each(function () {
                    imgh += "<div class=\"swiper-slide h100\"><img src=\""+$(this).attr('data-img')+"\"></div>";
                });
                $('#globalWrapper').html(imgh);
                lgtbox.show().removeClass('zoomOut').addClass('zoomIn').one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function () {
                    lgtbox.show();
                });
                imgswiper = new Swiper('#globalLightbox', {
                    pagination: '.lightbox-pagination',
                    paginationType: 'bullets',
                    loop: true
                });
                console.log($(this).parent().index())
                imgswiper.slideTo(that.parent().index()+1,0);
                return false;
            });
            $(document).on('click', '#globalLightbox', function () {
                lgtbox.removeClass('zoomIn').addClass('zoomOut').one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function () {
                    lgtbox.hide();
                    imgswiper.destroy();
                });
                return false;
            });

            $(document).on('click', '.sale_out_goods', function () {
                layer.open({
                    content: '已售罄'
                    ,skin: 'msg'
                    ,anim: 'up'
                    ,time: 2
                });
            });
        </script>

        <script id="groupCombineDialogTpl" type="text/html">
            <div class="group-combine-close groupCombineClose">
                <img src="{pigcms{$static_path}img/close_voice.png" alt="">
            </div>
            <div class="group-combine-title-wrap">
                <div class="group-combine-title">
                    <span>{{d.data.title}}</span>
                </div>
                <div class="group-combine-price-wrap">
                    <div class="group-combine-price">
                        <span class="text-price">{pigcms{$config['Currency_symbol']}{{d.data.price}}</span>
                        <span class="text-old-price">{pigcms{$config['Currency_symbol']}{{d.data.old_price}}</span>
                    </div>
                    <div class="group-combine-count">
                        <span>共{{d.data.group_list.length}}件商品</span>
                        <span class="group-combine-line">|</span>
                        <span>可使用{{d.data.can_use_count}}次</span>
                    </div>
                </div>
            </div>
            <div class="group-combine-list-wrap">
                <div class="group-combine-list">
                {{# for(var i = 0, len = d.data.group_list.length; i < len; i++){ }}
                    <div class="group-combine-item">
                        <div class="group-combine-img">
                            <img src="{{d.data.group_list[i].image}}" alt="">
                        </div>
                        <div class="group-combine-item-content">
                            <div class="text-left group-combine-item-name">
                                <span class="group-combine-name">{{d.data.group_list[i].name}}</span>
                                {{# if(d.data.group_list[i].distance && d.data.group_list[i].distance != 0){ }}
                                <span class="group-combine-distance">{{d.data.group_list[i].distance}}</span>
                                {{# }}}
                            </div>
                            <div class="margin-top-8">
                                <span class="text-price text-group-item-price">团购价:{pigcms{$config['Currency_symbol']}{{d.data.group_list[i].price}}</span>
                                {{# if(d.data.group_list[i].old_price && d.data.group_list[i].old_price != 0){ }}
                                <span class="text-old-price text-group-item-lod-price">门店价:{pigcms{$config['Currency_symbol']}{{d.data.group_list[i].old_price}}</span>
                                {{# }}}
                            </div>
                        </div>
                    </div>
                    {{# } }}
                </div>
                <div class="group-combine-btn">
                    <span class="color-06C1AE" id="groupCombineBuyBtn" data-title="提交订单" data-url="{pigcms{:U('Group/buy',array('group_id'=>$now_group['group_id'],'new_group'=>1,'share_uid'=>$_GET['share_uid'],'openid'=>$_GET['openid'],'share_openid'=> $_GET['share_openid']))}">直接购买</span>
                    <span class="color-fff bg-06C1AE" id="groupCombineDetail" data-combine_id="{pigcms{$groupCombine.combine_id}" data-group_id="{pigcms{$_GET['group_id']}" data-url="/packapp/plat/pages/group/groupCombineDetail">去看看</span>
                </div>
            </div>
        </script>
	</body>
</html>