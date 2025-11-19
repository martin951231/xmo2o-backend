<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$config.group_alias_name}详情</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?210"/>
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/detail.css?214"/>
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_detail.css?000010"/>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/fly-zomm-img.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/idangerous.swiper.min.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/common.js?216" charset="utf-8"></script>
		<script type="text/javascript"><if condition="$long_lat">var user_long = "{pigcms{$long_lat.long}",user_lat = "{pigcms{$long_lat.lat}";<else/>var user_long = '0',user_lat  = '0';</if></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/detail.js?225" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/group_detail.js?t=0000020"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
        <script>
            var json_info = '{pigcms{$json_info}';
            var group_buy = '{pigcms{:U('Group/buy',array('group_id'=>$now_group['group_id'],'share_uid'=>$_GET['share_uid'],'openid'=>$_GET['openid']))}';
            var group_now = '{pigcms{:U('Group/buy',array('group_id'=>$now_group['group_id'],'share_uid'=>$_GET['share_uid'],'openid'=>$_GET['openid']))}';
            var buy_spec_id = '{pigcms{$first['specifications_id']}';
            var buy_num = '{pigcms{$first['once_min']}';

            // 立即预定
            var buy_appoint = '{pigcms{:U('Appoint/detail',array('appoint_id'=>$now_group['appoint_id']))}';
            // 立即预定
            var buy_hotel = '{pigcms{:U('Group/buy',array('group_id'=>$now_group['group_id']))}';
            // 导航地址
            var group_map = '{pigcms{:U('Group/group_address_info',array('group_id'=>$now_group['group_id']))}';
        </script>
		<style type="text/css">
		.clock {
		    position: absolute;
		    display: inline-block;
		    right: 10px;
		    border: 1px solid #DADADA;
		    border-radius: 5px;
		    padding: 1px 3px;
		    top: 25px;
		    background: url(../tpl/Wap/pure/static/img/index/clock.png) no-repeat;
		    background-size: 14px;
		    background-position: 4px 4px;
		    padding-left: 22px;
		}
		.clock .timerBox {
		    letter-spacing: 0.5px;
		}
		.clock .timer {
		    color: #FF658D;
		}
		#mpackageslist li {
			width: 35%;
			text-align:center;
			border-radius: 4px;
			border: 1px solid #ddd;
			color: #666;
			display: inline-block;
			line-height: 27px;
			margin: 0 5px 5px 10px;
			max-width: 230px;
			overflow: hidden;
			padding-left: 8px;
			padding-right: 8px;
			position: relative;
			text-decoration: none;
			text-overflow: ellipsis;
			white-space: nowrap;
			list-style-type: none;
		}
		#mpackageslist .current {
			border: 2px solid #fe5842;
		}
		#mpackageslist .current a {
			color: #fe5842;
		}
		.packetDiv{
			padding: 10px 0 0px 12px;
			color: #999;
			border-bottom: 1px solid #f1f1f1;
		}
		.product_info_list_left {
			float: left;
			text-align: right;
			color: #999;
		}
		
  		.collection{ position: absolute; width: 35px; height: 35px; border-radius: 100%; background: url({pigcms{$static_path}images/3.png) center no-repeat; z-index: 9999; top:10px; right: 10px; }
		.collection:after{ display: block;  content: ''; width: 16px; height: 16px; background: url({pigcms{$static_path}images/2.png) center no-repeat; background-size: 16px; position: absolute; ); top: 50%; left: 50%;  margin: -8px; }
		.collection.on:after{ background: url({pigcms{$static_path}images/1.png) center no-repeat; background-size: 16px; }
		.discount{
			border: 1px solid #eda4a4;
			color: #cc0000;
			background-color: #fae6e6 !important;
		}
		.tag{
			border: 1px solid #fbd3a4;
			color: #f8a100;
			background-color:#fef3e6;
		}
        .detail-box{
            width: 100%;
            height: 46px;
            background: #fff;
            z-index: 20;
            box-sizing: border-box;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            align-items: center;
            top: 0px;

        }
		.swiper_slide_fixed{
			top: 0px;
			background: #fff;
			z-index: 20;
			box-sizing: border-box;
			border-bottom: 1px solid rgba(0, 0, 0, 0.05);
			align-items: center;
			position:fixed !important;
			left:0px;
			right:0px;
			min-width:100%

		}
        .detail-box div{line-height:46px;text-align:center;  }
        .detail-box div.actived{color: #06c1ae;  border-bottom: 2px solid #06c1ae;}
		.swiper-container {
			margin: 0 auto;
			position: relative;
			overflow: hidden;
			-webkit-backface-visibility: hidden;
			-moz-backface-visibility: hidden;
			-ms-backface-visibility: hidden;
			-o-backface-visibility: hidden;
			backface-visibility: hidden;
			z-index: 1;
			width: 100%;
			height: 100%;
		}
		 .swiper-wrapper {
			position: relative;
			width: 100%;
			z-index: 1;
			display: -webkit-box;
			display: -moz-box;
			display: -ms-flexbox;
			-webkit-transform-style: preserve-3d;
			-moz-transform-style: preserve-3d;
			-ms-transform-style: preserve-3d;
			transform-style: preserve-3d;
			-webkit-transition-property: -webkit-transform;
			-moz-transition-property: -moz-transform;
			-o-transition-property: -o-transform;
			-ms-transition-property: -ms-transform;
			transition-property: transform;
			-webkit-box-sizing: content-box;
			-moz-box-sizing: content-box;
			box-sizing: content-box;
			 min-width:100%;
		}
		.flex{
			display: -webkit-box;  /* 老版本语法: Safari, iOS, Android browser, older WebKit browsers. */
			display: -moz-box;     /* 老版本语法: Firefox (buggy) */
			display: -ms-flexbox;  /* 混合版本语法: IE 10 */
			display: -webkit-flex; /* 新版本语法: Chrome 21+ */
			display: flex;
		}
        .swiper-slide{
            flex:1;
        }
        .line-tip {
            color: #06c1ae;
            border: #06c1ae 1px solid;
            display: inline-block;
            border-radius: 5px;
            padding: 1px 3px;
            font-size: 10px;
            margin-right: 5px;
        }
        .tip_div .tip-info {
            font-size: 10px;
            display: inline-block;
        }
        .goodList li.more:after {
            margin-right: 10px;
        }
        .goodList li .title {
            height: 16px;
            line-height: 16px;
        }
        .msg_div {
        }
        .line-msg {
            color: #06c1ae;
            border: #06c1ae 1px solid;
            display: inline-block;
            border-radius: 5px;
            padding: 1px 3px;
            font-size: 10px;
            margin-right: 5px;
            margin-top: 3px;
        }
        .msg_div .msg-info {
            font-size: 10px;
            display: inline-block;
            max-height: 50px;
            overflow: hidden;
        }
        .detail .content img {
            max-width: 100%;
            _width: 100%;
            /* margin: 10px 0; */
            display: inline-block;
            vertical-align: middle;
            height: auto;
        }
            .introList {
                border: none;
            }
        </style>
		
		<script type="text/javascript">
		  $(function(){
            $(".mask").height($(window).height());
		    $(".collection").click(function(){
		    	var uid = "{pigcms{$user_session['uid']}";
			  	if(!uid){
		  			layer.open({content:'请先登录再进行收藏！',btn: ['确定'],end:function(){location.href="{pigcms{:U('Login/index')}";}});
		  			return false;
			  	}

	      	if($(this).hasClass("on")){
		        $(this).removeClass("on");
				$.post('{pigcms{:U('My/ajax_group_collect')}', {id: {pigcms{$_GET['group_id']},type:'group_detail',action:'del'}, function(data, textStatus, xhr) {
					layer.open({
					    content: data.info
					    ,skin: 'msg'
					    ,anim: 'up'
					    ,time: 2 //2秒后自动关闭
				  	});
				});
		   
		      }else{
		        $(this).addClass("on");
				$.post('{pigcms{:U('My/ajax_group_collect')}', {id: {pigcms{$_GET['group_id']},type:'group_detail',action:'add'}, function(data, textStatus, xhr) {
					layer.open({
					    content: data.info
					    ,skin: 'msg'
					    ,anim: 'up'
					    ,time: 2 //2秒后自动关闭
				  	});
				});
		   
		      }
		    })
		  })
		</script>

	</head>
	<body>
        <!-- 规格选项 -->
        <section class="Speci">
            <div class="title">
                <h2>{pigcms{$now_group.group_name}</h2>
                <div class="title_Price clr">
                    <span class="tuftof">{pigcms{$config['Currency_symbol']}<i id="show_format_price">{pigcms{$first['price']}</i><if condition="$first['old_price'] neq '' && $first['old_price'] neq $first['price']"><span class="old">{pigcms{$config['Currency_symbol']}<del id="old_price">{pigcms{$first.old_price}</del></if></span>
                    <if condition="$first['count_num'] elt 0">
                        <span class="Surplus fl" id="show_stock_num">库存充足 </span>
                    <else/>
                        <span class="Surplus fl" id="show_stock_num">剩余{pigcms{$first['surplus']}份</span>
                    </if>
                    <if condition="$now_group['once_min'] gt 1">
                        <span class="Discount" style="margin-left: 10px;" id="once_min">{pigcms{$first['once_min']}份起购</span>
                    </if>
                </div>
                <if condition="$first['today_can_buy'] egt 0">
                    <div class="Discount" id="today_can_buy_now" style="width: fit-content;">当前可购买{pigcms{$first['today_can_buy']}份</div>
                </if>
                <a href="javascript:void(0)" class="del"></a>
            </div>
            <div class="cations clr spec">
                <span class="fl cas_left">规格：</span>
                <div class="p35">
                    <ul data-type="properties">
                        <volist name="group_specifications" id="vo" key="index">
                            <li class="spec-li <if condition='$index eq 1'>on</if>" data-count_num="{pigcms{$vo['count_num']}" data-surplus="{pigcms{$vo['surplus']}" data-today_can_buy="{pigcms{$vo['today_can_buy']}" data-id="{pigcms{$vo['specifications_id']}" data-name="{pigcms{$vo['specifications_name']}">{pigcms{$vo['specifications_name']}</li>
                        </volist>
                    </ul>
                </div>
            </div>
            <input id="once_min_msg" type="hidden" value="{pigcms{$first['once_min']|default=1}">
            <input id="once_max_msg" type="hidden" value="{pigcms{$first['once_max']|default=0}">
            <input id="once_max_day_msg" type="hidden" value="{pigcms{$first['once_max_day']|default=0}">
            <input id="today_can_buy" type="hidden" value="{pigcms{$first['today_can_buy']|default=0}">
            <div class="cations clr num-info">
                <span class="fl cas_left">数量</span>
                <div class="wrap">
                    <div class="plus_group clr">
                        <a data-num="{pigcms{$first['once_min']}" href="javascript:void(0)" class="jian">-</a>
                        <input type="text" value="{pigcms{$first['once_min']|default=1}" readonly="readonly">
                        <a data-num="{pigcms{$first['once_min']}" href="javascript:void(0)" class="jia">+</a>
                    </div>
                </div>
            </div>
            <!-- 新增 -->
            <div class="purchase_right  purchase_right100 clr">
                <a href="javascript:void(0);" class="fl shops shops100 one_btn" data-type="add">确定</a>
            </div>

        </section>
        <div class="mask"></div>
        <!--<section class="detail-box" style="display:flex">
            <div>产品特色</div>
            <div class="active">4日行程</div>
            <div>费用说明</div>
            <div>预订须知</div>
        </section>-->
        <div id="" >
            <div id="" style="padding-bottom:50px;">
				<div id="pullDown" style="background-color:#06c1ae;color:white;">
					<span class="pullDownLabel" style="padding-left:0px;"><i class="yesLightIcon" style="margin-right:10px;vertical-align:middle;"></i>{pigcms{$config.wechat_name} 精心为您优选</span>
				</div>
				<section class="imgBox">
					<img src="{pigcms{$now_group.all_pic.0.m_image}" class="view_album" data-pics="<volist name="now_group['all_pic']" id="vo">{pigcms{$vo.m_image}<if condition="count($now_group['all_pic']) gt $i">,</if></volist>"/>
					<div class="imgCon">
						<div class="title">{pigcms{$now_group.group_name}</div>
						<div class="desc">{pigcms{$now_group.s_name}</div>
					</div>
					<div class="back"></div>
					<div class="collection <if condition="$is_collect">on</if>"></div>
				</section>

				<section class="buyBox">
					<div class="priceDiv">
                        <span class="price">
                        	{pigcms{$config['Currency_symbol']}<strong>{pigcms{$now_group['price']}
                        	<if condition="$now_group['extra_pay_price'] gt 0 AND $config['open_extra_price'] eq 1">+{pigcms{$now_group.extra_pay_price|floatval}{pigcms{$config.extra_price_alias_name}</if></strong>
                        	<if condition="$now_group['old_price'] neq '' && $now_group['old_price'] neq $now_group['price']"><span class="old">{pigcms{$config['Currency_symbol']}<del>{pigcms{$now_group.old_price}</del></span></if>
                        	<!-- 非多规格存在库存的团购商品 -->
                        	<if condition="!$group_specifications && isset($now_group['surplus_num']) && $now_group['surplus_num'] elt 10">                        		
                        		<span class='old'>
                        			<gt name="now_group.surplus_num" value='0'>
                        				库存剩余：{pigcms{$now_group.surplus_num|intval}
                        			<else/>
                        				已卖完
                        			</gt>
                        		</span>                    		
                        	</if>
                       	</span>

						<if condition="$now_group['begin_time'] gt $_SERVER['REQUEST_TIME']">
							<span class="clock"><span class="time_d">{pigcms{$time_array['d']}</span>天 <span class="timerBox"><span class="timer time_h">{pigcms{$time_array['h']}</span>:<span class="timer time_m">{pigcms{$time_array['m']}</span>:<span class="timer time_s">{pigcms{$time_array['s']}</span></span></span>
						<elseif condition="$now_group['end_time'] gt $_SERVER['REQUEST_TIME'] AND $now_group['begin_time'] lt $_SERVER['REQUEST_TIME'] AND $now_group['type'] eq 1" />
							<if condition='$now_group["is_appoint_bind"]'>
								<a class="btn buy-btn btn-large btn-strong buy-appoint" href="{pigcms{:U('Appoint/detail',array('appoint_id'=>$now_group['appoint_id'],'share_uid'=>$_GET['share_uid'],'openid'=>$_GET['openid']))}">立即预约</a>
                            <elseif condition="$now_group['trade_type'] eq 'hotel'"/>
                                <a class="btn buy-btn btn-large btn-strong buy-hotel" href="{pigcms{:U('Group/buy',array('group_id'=>$now_group['group_id'],'share_uid'=>$_GET['share_uid'],'openid'=>$_GET['openid']))}">立即预订</a>
                            <elseif condition="$group_specifications"/>
                                <a class="btn buy-btn btn-large btn-strong buy-info">立即购买</a>
                            <else />
                                <a class="btn buy-btn btn-large btn-strong buy-now" href="{pigcms{:U('Group/buy',array('group_id'=>$now_group['group_id'],'uid'=>$_GET['uid'],'share_uid'=>$_GET['share_uid'],'openid'=>$_GET['openid']))}">立即购买</a>
							</if>
						</if>
					</div>
					<if condition="$now_group['wx_cheap'] OR ($now_group['discount'] gt 0 AND $now_group['vip_discount_type'] gt 0)">
                        <if condition="$is_app_browser">
                        <div class="cheapDiv">优惠 <span class="tag">APP购买再减{pigcms{$now_group.wx_cheap}{pigcms{$config['Currency_txt']}</span></div>
                        <else/>
						<div class="cheapDiv">优惠 <php>if($now_group['discount'] > 0 && $now_group['vip_discount_type'] > 0){</php><span class="tag discount">{pigcms{$now_group['discount']|floatval}折</span><php>}</php>
						
						<php>if($now_group['wx_cheap']){</php><span class="tag">微信购买再减{pigcms{$now_group.wx_cheap}{pigcms{$config['Currency_txt']}</span><php>}</php></div>
                        </if>
					</if>
					
					<if condition="$merchant_card_spread_info && $merchant_card_spread_info['card_discount']">
						<if condition="empty($user_session)">
							<div class="cheapDiv link-url" data-url="{pigcms{:U('Login/index')}">领取商家会员卡结算 <span style="color:red;">{pigcms{$merchant_card_spread_info.card_discount}折</span> 优惠 <span class="tag">请先登录</span></div>
						<elseif condition="!$merchant_card_spread_info['get_card']"/>
							<div class="cheapDiv link-url" data-url="{pigcms{$merchant_card_spread_info.url}">领取商家会员卡结算 <span style="color:red;">{pigcms{$merchant_card_spread_info.card_discount}折</span> 优惠 <span class="tag">点击领取</span></div>
						<else/>
							<div class="cheapDiv link-url" data-url="{pigcms{$merchant_card_spread_info.url}">领取商家会员卡结算 <span style="color:red;">{pigcms{$merchant_card_spread_info.card_discount}折</span> 优惠 <span class="tag">已经领取</span></div>
						</if>
					</if>
					
					<if condition="$now_group.group_share_num gt 0">
						<div class="cheapDiv">组团 <span class="tag" style="border:0px">您需要购买或者邀请好友购买{pigcms{$now_group.group_share_num}份才能成团</span></div>
					<elseif condition="$now_group.open_now_num gt 0" />
						<div class="cheapDiv">拼团 <span class="tag" style="border:0px">还差{pigcms{$now_group.open_now_num}份成团</span></div>
					<elseif condition="$now_group.open_num gt 0" />
						<div class="cheapDiv">拼团 <span class="tag" style="border:0px">还差{pigcms{$now_group.open_num}份成团</span></div>
					</if>

					<if condition="isset($mpackages) AND !empty($mpackages)">
					<div class="packetDiv" id="mpackageslist">
						<table style="width:100%">
						<tr>
						<td width="15%" valign="top">套餐：</td>
						<td>
						 <ul style="width: 90%;">
						   <volist name="mpackages" id="vv">
							  <a href="{pigcms{:U('Group/detail',array('group_id'=>$key))}"><li <if condition="$key eq $now_group['group_id']"> class="current"</if> >{pigcms{$vv}</li></a>
						   </volist>
						</ul>
						</td>
						</tr>
						</table>
					</div>
					</if>
					<if condition="empty($user_session) && $config['user_score_max_use']">
						 <div class="cheapDiv link-url" data-url="{pigcms{:U('Login/index')}">{pigcms{$config.score_name}抵现 <span class="tag">请先登录查看可抵现金额</span></div>
					<elseif condition="$user_coupon_use['score']"/>
                        <div class="cheapDiv">{pigcms{$config.score_name}抵现 <span class="tag">本单最高可用{pigcms{$user_coupon_use.score}{pigcms{$config.score_name}抵{pigcms{$user_coupon_use.score_money}{pigcms{$config['Currency_txt']}</span></div>
					</if>



                    <if condition="$now_group['field_info']">
                        <div class="cheapDiv">
                            <div class="tip_div">
                                <div class="tip-info">
                                    <volist name="now_group['field_info']" id="vv">
                                        <div class="line-tip">
                                            {pigcms{$vv}
                                        </div>
                                    </volist>
                                </div>
                            </div>
                        </div>
                    </if>

					<if condition="$now_group['no_refund'] eq 1">
						<div class="saleDiv">不支持退款
						<if condition="$now_group.sale_txt neq ''"><span class="sale" ><i class="yesIcon"></i>{pigcms{$now_group['sale_txt']}</span></if>
						</div>
					</if>
					<if condition="$now_group['trade_type']!='hotel' AND $now_group['no_refund'] eq 0 AND $now_group['before_effective_time'] gt 0">
                        <div class="saleDiv">
                            <span><i class="yesLightIcon"></i>团购券有效期前{pigcms{$now_group['before_effective_time']}<if condition="$now_group['effective_time_type'] eq 1">天<else/>小时</if>前可退</span>
                            <if condition="$now_group.sale_txt neq ''"><span class="sale"><i class="yesIcon"></i>{pigcms{$now_group['sale_txt']}</span></if>
                        </div>
                        <elseif condition="$now_group['trade_type']!='hotel' AND $now_group['no_refund'] eq 0 AND $now_group['before_effective_time'] eq 0"/>
                        <div class="saleDiv">
                            <span><i class="yesLightIcon"></i>随时退</span>
                            <span><i class="yesLightIcon"></i>过期退</span>
                            <if condition="$now_group.sale_txt neq ''"><span class="sale"><i class="yesIcon"></i>{pigcms{$now_group['sale_txt']}</span></if>
                        </div>
					</if>
				</section>
					
				<if condition="!empty($reply_list)">
					<section class="scoreBox link-url" data-url="{pigcms{:U('Group/feedback',array('group_id'=>$now_group['group_id']))}">
						<div class="rateInfo">
							<div class="starIconBg"><div class="starIcon" style="width:{pigcms{$now_group['score_mean']*20}%;"></div></div>
							<div class="starText">{pigcms{$now_group.score_mean}</div>
							<div class="right">{pigcms{$now_group.reply_count} 人评价</div>
						</div>
					</section>
				</if>
				<section class="storeBox">
					<dl class="storeList">
						<volist name="now_group['store_list']" id="vo" offset="0" length="2">
							<dd class="link-url" data-url="<if condition="$config.open_extra_price eq 1">{pigcms{:U('Shop/merchant_shop',array('store_id'=>$vo['store_id']))}<else />{pigcms{:U('Group/shop',array('store_id'=>$vo['store_id']))}</if>">
								<div class="name">{pigcms{$vo.name}</div>
								<div class="address">{pigcms{$vo.area_name}{pigcms{$vo.adress}</div>
								<if condition="$vo['range']"><div class="position"><div class="range">{pigcms{$vo.range}</div><if condition="$i eq 1"><div class="desc">离我最近</div></if></div></if>
								<div class="phone" data-phone="{pigcms{$vo.phone}"></div>
							</dd>
						</volist>
					</dl>
					<if condition="count($now_group['store_list']) gt 2">
						<div class="more link-url" data-url="{pigcms{:U('Group/branch',array('group_id'=>$now_group['group_id']))}">全部{pigcms{:count($now_group['store_list'])}家分店</div>
					</if>
				</section>
			   <if condition="!empty($group_content)">
                   <php>if ($config['group_content_switch'] == 1) {</php>
                       <section class="detail-box"  id="box_title" style="padding:0px 10px">
                               <div class="swiper-container ">
                                   <div class="swiper-wrapper">
                                       <if condition="$now_group['cue_arr'] || $now_group['address_detail']">
                                          <div class="group_name0 actived swiper-slide" data-index="0">购买须知</div>
                                             <volist name="group_content" id="group">
                                                   <div class="swiper-slide group_name{pigcms{$key+1} "  data-index="{pigcms{$key+1}">{pigcms{$group.name}</div>
                                             </volist>
                                         <else/>
                                             <volist name="group_content" id="group">
                                               <div class="swiper-slide group_name{pigcms{$key} "  data-index="{pigcms{$key}">{pigcms{$group.name}</div>
                                             </volist>
                                       </if>
                                   </div>
                               </div>
                       </section>
                   <php>}</php>
				   <if condition="$now_group['cue_arr'] || $now_group['address_detail']">
					   <section class="term introList group_content0">
                           <div class="titleDiv"><div class="title">购买须知</div></div>
						   <div class="content">
							   <ul>
                                   <php>
                                       if($now_group['address_detail']){
                                   </php>
<!--                                      <li><b>详细地址：</b>{pigcms{$now_group.address_detail}</li>-->
                                       <li>
                                           <div class="details_top clearfix">
                                               <p class="c6 f14 group_map"><b>详细地址：</b>
                                                   <em><img src="{pigcms{$static_path}images/dindxqt_11.png" width="18" height="20" style="position: relative; top: 5px;"/></em>
                                                   <span>{pigcms{$now_group.address_detail}</span>
                                               </p>
                                           </div>
                                       </li>
                                   <php>}</php>
								   <volist name="now_group['cue_arr']" id="vo">
									   <if condition="$vo['value']">
										   <li><b>{pigcms{$vo.key}：</b>{pigcms{$vo.value|nl2br=###}</li>
									   </if>
								   </volist>
							   </ul>
						   </div>
					   </section>
					   <volist name="group_content" id="group_name">
						   <section class="detail introList group_content group_content{pigcms{$key+1}">
                               <div class="titleDiv"><div class="title">{pigcms{$group_name.name}</div></div>
							   <div class="content ">{pigcms{$group_name.content}</div>
						   </section>
					   </volist>
					<else/>
					   <volist name="group_content" id="group_name">
						   <section class="detail introList group_content group_content{pigcms{$key}">
                               <div class="titleDiv"><div class="title">{pigcms{$group_name.name}</div></div>
							   <div class="content ">{pigcms{$group_name.content}</div>
						   </section>
					   </volist>
				   </if>
				<else/>
				   <if condition="$now_group['cue_arr'] || $now_group['address_detail']">
					   <section class="term introList">
						   <div class="titleDiv"><div class="title">购买须知</div></div>
						   <div class="content">
							   <ul>
                                   <php>
                                       if($now_group['address_detail']){
                                   </php>

                                   <li>
                                       <div class="details_top clearfix">
                                           <p class="c6 f14 group_map"><b>详细地址：</b>
                                               <em><img src="{pigcms{$static_path}images/dindxqt_11.png" width="18" height="20" style="position: relative; top: 5px;"/></em>
                                               <span>{pigcms{$now_group.address_detail}</span>
                                           </p>
                                       </div>
                                   </li>

                                   <php>}</php>
								   <volist name="now_group['cue_arr']" id="vo">
									   <if condition="$vo['value']">
										   <li><b>{pigcms{$vo.key}：</b>{pigcms{$vo.value|nl2br=###}</li>
									   </if>
								   </volist>
							   </ul>
						   </div>
					   </section>
				   </if>
			   </if>
				<if condition="!empty($reply_list)">
					<section class="comment introList">
						<div class="titleDiv"><div class="title">评价<div class="rateInfo"><div class="starIconBg"><div class="starIcon" style="width:{pigcms{$now_group['score_mean']*20}%;"></div></div><div class="starText">{pigcms{$now_group.score_mean}</div></div><div class="right">{pigcms{$now_group.reply_count} 人评论</div></div></div>
						<dl>
							<volist name="reply_list" id="vo">
								<dd>
									<div class="titleBar">
										<div class="nickname">{pigcms{$vo.nickname}</div><div class="dateline">{pigcms{$vo.add_time}</div><div class="rateInfo"><div class="starIconBg"><div class="starIcon" style="width:{pigcms{$vo['score']*20}%;"></div></div></div>
									</div>
									<div class="replyCon">
										<div class="textDiv">
											<div class="text">{pigcms{$vo.comment}</div>
										</div>
										<if condition="$vo['pics']">
											<ul class="imgList" data-pics="<volist name="vo['pics']" id="voo">{pigcms{$voo.m_image}<if condition="count($vo['pics']) gt $i">,</if></volist>">
												<volist name="vo['pics']" id="voo">
													<li><img src="{pigcms{$voo.s_image}"/></li>
												</volist>
											</ul>
										</if>
										<if condition="$vo['merchant_reply_content']">
										<div class="textDiv">
											<div class="text" style=" font-size: 12px;color: #C6895A;">商家回复：{pigcms{$vo.merchant_reply_content}</div>
										</div>
										</if>
									</div>
								</dd>
							</volist>
						</dl>
						<if condition="$now_group['reply_count'] gt 3">
							<div class="more link-url" data-url="{pigcms{:U('Group/feedback',array('group_id'=>$now_group['group_id']))}">查看全部 {pigcms{$now_group.reply_count} 条评价</div>
						</if>
					</section>
				</if>
				<if condition="$merchant_group_list">
					<section class="storeProList introList">
						<div class="titleDiv"><div class="title">商家其他{pigcms{$config.group_alias_name}</div></div>
						<ul class="goodList">
							<volist name="merchant_group_list" id="vo" key="index">
								<li class="link-url" data-url="{pigcms{$vo.url}" <if condition="$index gt 2">style="display:none;"</if>>
									<div class="dealcard-img imgbox">
										<img src="{pigcms{$config.site_url}/index.php?c=Image&a=thumb&width=276&height=168&url={pigcms{:urlencode($vo['list_pic'])}" alt="{pigcms{$vo.name}"/>
									</div>
									<div class="dealcard-block-right">
										<div class="title">{pigcms{$vo.group_name}</div>
										<div class="price">
											<strong>{pigcms{$vo['price']}</strong><span class="strong-color">{pigcms{$config['Currency_txt']}<if condition="$vo.extra_pay_price neq ''">{pigcms{$vo.extra_pay_price}</if></span><if condition="$vo['wx_cheap']"><span class="tag"><?php if($is_app_browser && in_array($app_browser_type,array('android','ios'))){ ?>APP<?php }else{ ?>微信<?php } ?>再减{pigcms{$vo.wx_cheap}{pigcms{$config['Currency_txt']}</span></if><span class="line-right">{pigcms{$vo['sale_txt']}</span>
										</div>
                                        <if condition="$vo['field_info']">
                                            <div class="msg_div">
                                                <div class="msg-info">
                                                    <volist name="vo['field_info']" id="vv">
                                                            <div class="line-msg">
                                                                {pigcms{$vv}
                                                            </div>
                                                    </volist>
                                                </div>
                                            </div>
                                        </if>
									</div>
								</li>
							</volist>
							<if condition="count($merchant_group_list) gt 2"><li class="more">其他{pigcms{:count($merchant_group_list)-2}个{pigcms{$config.group_alias_name}</li></if>
						</ul>
					</section>
				</if>
				<if condition="$category_group_list && $merchant_link_showOther && empty($_GET['source'])">
					<section class="sysProList introList">
						<div class="titleDiv"><div class="title">看了本{pigcms{$config.group_alias_name}的用户还看了</div></div>
						<dl class="likeBox dealcard">
							<volist name="category_group_list" id="vo">
								<dd class="link-url" data-url="{pigcms{$vo.url}">
									<div class="dealcard-img imgbox">
										<img src="{pigcms{$config.site_url}/index.php?c=Image&a=thumb&width=276&height=168&url={pigcms{:urlencode($vo['list_pic'])}" alt="{pigcms{$vo.name}"/>
									</div>
									<div class="dealcard-block-right">
										<div class="brand">{pigcms{$vo.s_name}<if condition="$vo['range_txt']"><span class="location-right">{pigcms{$vo.range_txt}米</span></if></div>
										<div class="title">[{pigcms{$vo.prefix_title}]{pigcms{$vo.intro}</div>
										<div class="price">
											<strong>{pigcms{$vo['price']}</strong><span class="strong-color">{pigcms{$config['Currency_txt']}<if condition="$vo.extra_pay_price neq ''">{pigcms{$vo.extra_pay_price}</if></span><if condition="$vo['wx_cheap']"><span class="tag"><?php if($is_app_browser && in_array($app_browser_type,array('android','ios'))){ ?>APP<?php }else{ ?>微信<?php } ?>再减{pigcms{$vo.wx_cheap}{pigcms{$config['Currency_txt']}</span></if><span class="line-right">{pigcms{$vo['sale_txt']}</span>
										</div>
                                        <if condition="$vo['field_info']">
                                            <div class="msg_div">
                                                <div class="msg-info">
                                                    <volist name="vo['field_info']" id="vv">
                                                        <div class="line-msg">
                                                            {pigcms{$vv}
                                                        </div>
                                                    </volist>
                                                </div>
                                            </div>
                                        </if>
									</div>
								</dd>
							</volist>
						</dl>
					</section>
				</if>
				<!--<div id="pullUp" style="bottom:-60px;">
					<img src="{pigcms{$config.site_logo}" style="width:130px;height:40px;margin-top:10px"/>
				</div>-->
			</div>
		</div>
		<php>if($now_group['pin_num']==0){</php>
		<div class="positionDiv">
			<div class="left"><div class="back"></div></div>
			<if condition="$now_group['tuan_type'] neq 2">
				<div class="center">{pigcms{$now_group.merchant_name}</div>
			<else/>
				<div class="center">{pigcms{$now_group.s_name}</div>
			</if>
			<if condition="$now_group['end_time'] gt $_SERVER['REQUEST_TIME'] AND $now_group['begin_time'] lt $_SERVER['REQUEST_TIME'] AND $now_group['type'] eq 1">
				<div class="right">
                    <if condition="$group_specifications">
                        <a class="btn buy-btn btn-large btn-strong buy-info">购买</a>
                        <else />
                        <a class="btn buy-btn btn-large btn-strong buy-now" href="{pigcms{:U('Group/buy',array('group_id'=>$now_group['group_id'],'uid'=>$_GET['uid']))}">购买</a>
                    </if>
				</div>
			</if>
		</div>
					<php>}</php>
		<php>$no_footer=true;</php>
		<include file="Public:footer"/>
		<script type="text/javascript">
			var show_detail=false;
			$(function(){
				$('#group_rule').click(function(){
					if(show_detail){
						$('.group_rule').removeClass('more');
						show_detail = false;
					}else{
						$('.group_rule').addClass('more');
						show_detail = true;
					}
				});
				
				<if condition="$start_num gt 0 AND empty($start_head)">
					$('.star_group').css('height','230px');
				</if>
			});
			
			 <if condition="$group_share_info">
				var h={pigcms{$effective_time.h};
				var m={pigcms{$effective_time.m};
				var s={pigcms{$effective_time.s};
				time=setInterval("run()",1000);
				
				function run(){
					--s;
					if(s<0){
						--m;
						s=59;
					}
					if(m<0){
						--h;
						m=59
					}
					if(h<0){
						s=0;
						m=0;
					}
					$('.timeDown').html(h+":"+m+":"+s);
				}
			</if>
			 
			<if condition="$now_group['begin_time'] gt $_SERVER['REQUEST_TIME']">
			var h = {pigcms{$time_array['h']};
			var m = {pigcms{$time_array['m']};
			var s = {pigcms{$time_array['s']};
			function run_time(){
				--s;
				if(s<0){
					--m;
					s=59;
				}
				if(m<0){
					--h;
					m=59
				}
				if(h<0){
					s=0;
					m=0;
				   window.location.reload();
				}
				$('.time_h').html(h);
				$('.time_m').html(m);
				$('.time_s').html(s);
			}
			time=setInterval("run_time()",1000);
		</if>
			
			window.shareData={
				"moduleName":"Group",
				"moduleID":"0",
				"imgUrl": "<if condition="$now_group['all_pic'][0]['m_image']">{pigcms{$now_group.all_pic.0.m_image}<else/>{pigcms{$config['wechat_share_img']}</if>",
				"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Group/detail', array('group_id' => $now_group['group_id'],'uid'=>$user_session['uid']))}",
				"tTitle": "{pigcms{$now_group.price}{pigcms{$config['Currency_txt']} 【{pigcms{$now_group.s_name}】",
				"tContent": "{pigcms{$now_group['intro']|msubstr=0,40}"
			};
		</script>
		{pigcms{$shareScript}
		<include file="kefu" />
        <if condition="$is_app_browser">
            <script type="text/javascript">
                
                if("{pigcms{$app_browser_type}" == 'android'){
					window.lifepasslogin.shareLifePass("{pigcms{$now_group.price}{pigcms{$config['Currency_txt']} 【{pigcms{$now_group.s_name}】","{pigcms{$now_group.intro}","<if condition="$now_group['all_pic'][0]['m_image']">{pigcms{$now_group.all_pic.0.m_image}<else/>{pigcms{$config['wechat_share_img']}</if>","{pigcms{$config.site_url}{pigcms{:U('Group/detail', array('group_id' => $now_group['group_id']))}");
				} else {
					iosCommon.iosFunction('shareLifePass', {name:'shareLifePass', imgUrl: window.shareData.imgUrl, sendFriendLink:window.shareData.sendFriendLink, tTitle:window.shareData.tTitle, tContent:window.shareData.tContent });
				}
            </script>
        </if>
		<if condition="$merchant_card_spread_info && $merchant_card_spread_info['card_discount'] && !$merchant_card_spread_info['get_card']">
			<script>
				$.post("{pigcms{:U('Home/get_merchant_card_spread_info')}",{mer_id:{pigcms{$now_group.mer_id}},function(result){
					if(!result.info.get_card){
						layer.open({
							content: '领取商家会员卡结算 <span style="color:red;">'+result.info.card_discount+'折</span> 优惠'
							,btn: ['领取', '不要']
							,yes: function(index){
								layer.close(index);
								location.href = result.info.url;
							}
						});
					}else{
						location.reload();
					}
				});
			</script>
		</if>
	</body>
</html>