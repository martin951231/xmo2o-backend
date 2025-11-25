<?php if (!defined('PigCms_VERSION')) {exit('deny access!');}?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8" />
	<title>商品详情</title>
	<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
	<meta name="apple-mobile-web-app-capable" content="yes"/>
	<meta name='apple-touch-fullscreen' content='yes'/>
	<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
	<meta name="format-detection" content="telephone=yes"/>
	<meta name="format-detection" content="address=no"/>
	<link href="{pigcms{$static_path}mall/css/photo.css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css"/>
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/goods.css?t={pigcms{:time()}"/>
	<script>
		var $home=false;
		var main_color='{pigcms{$config["mobile_head_color"]}'
	</script>
</head>
<body style="padding-bottom: 70px;">
<div class="goods-box">
	<div class="img-box">
		<div class="swiper-container" id='goodsImgBox'>
			<div class="swiper-wrapper pics">
				<if condition="is_array($goods['all_pic'])">
					<volist name="goods.all_pic" id="vo">
						<div class="swiper-slide">
							<div><img class="img " src="{pigcms{$vo.image}" data-pics="{pigcms{$vo.image}" data-index="{pigcms{$i}"></div>
						</div>
					</volist>
					<else />

					<div class="swiper-slide">
						<div><img class="img " src="{pigcms{$goods.image}" data-pics="{pigcms{$goods.image}" data-index="0"></div>
					</div>
				</if>

			</div>
			<div class="swiper-pagination"></div>
		</div>

		<if condition="$label[$goods['label']]['type'] eq 1">
			<img class="icon" src="{pigcms{:file_domain()}{pigcms{$label[$goods['label']]['image']}">
		</if>
		<if condition="!empty($label[$goods['label']]) AND $label[$goods['label']]['type'] eq 0 AND $goods['label'] neq 0">
			<span class="icon icon-text" style="background:linear-gradient(to bottom,{pigcms{$label[$goods['label']]['color']} , {pigcms{$label[$goods['label']]['color2']})">{pigcms{$label[$goods['label']]['name']}</span>
		</if>


		<if condition="($goods['stock_num']-$goods['sell_count']) elt 0">
			<div class="sell-out"><span>已售罄</span></div>
		<elseif condition="$goods['end_time']+86400 lt time()" />
			<div class="sell-out"><span>活动已结束</span></div>
		<elseif condition="$goods['start_time'] gt time()" />
			<div class="sell-out"><span>还未开始</span></div>
		</if>

		<div class="img-num"><span class="now">1</span>&nbsp;/&nbsp;<span class="total">3</span></div>
	</div>
	<div class="bg-cover ks-clear price-box" style="background-image: url({pigcms{$static_path}villagegroup/images/timeBg.jpg);<if condition='$config["mobile_head_color"]'>background: {pigcms{$config["mobile_head_color"]};</if>">
		<div class="fl">
			<div>
                {pigcms{$config['Currency_symbol']}
				<span class="font_size22">
					{pigcms{$goods.price|floatval}
				</span>
				<if condition="$increase_price gt 0 AND $is_leader">
                    +{pigcms{$config['Currency_symbol']}
					<span class="font_size22">
						{pigcms{$increase_price}
					</span>
				</if>
				<span class="priceAdd" style='display:none'><span>加</span><span class='newMoney'></span></span>
				<gt name='goods.old_price' value='$goods.price'>
					&nbsp;&nbsp;<span class="line-through">{pigcms{$config['Currency_symbol']}{pigcms{$goods.old_price|floatval}</span>
				</gt>
			</div>
			<div><i class="icon iconfont font_size12 mgr5">&#xe617;</i>累计{pigcms{$goods['sell_count']|intval}份&nbsp;<if condition="$category.show_tag eq 1">|&nbsp;已拼{pigcms{$all_wait_hadpull|intval}份</if></div>
		</div>
		<if condition="$goods.end_time gt 0 AND (($goods['end_time']+86400) gt time() OR $goods['start_time'] gt time() )">
			<div class="fr time-box">
				<div class="mgt5">距离拼团<if condition="$goods.start_time gt time()">开始<else />结束</if>还剩</div>
				<div class="mgt10 count-down" id="time">
					<div class="dis-inblock day-box" >
                        <span id="day" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};</if>" >00</span>&nbsp;天
                    </div>
                    <span id="hour" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};</if>" >00</span>&nbsp;:
                    <span id="min" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};</if>" >00</span>&nbsp;:
                    <span id="sec" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};</if>" >00</span>
                </div>
			</div>
		</if>
	</div>
	<div class="desc-box">
		<div class="ks-clear">
			<div class="fl ellipsis name">
				<if condition="$goods.open_time eq 1 && $category.show_tag eq 1"><span class="future-tip">预售</span></if>
                <if condition="$goods.own_sell eq 1 "><span class="self-tip">自营</span></if>
                <if condition="$goods.village_group_type eq 1 "><span class="self-tip">周期购</span></if>
				{pigcms{$goods.name}
			</div>
			<div class="fr share-box commonShare" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};</if>" >
				<div class="icon-box" style="<if condition='$config["mobile_head_color"]'>border-color: {pigcms{$config["mobile_head_color"]};</if>"><i class="icon iconfont" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};</if>" >&#xe612;</i></div>
				<div>分享好友</div>
			</div>
		</div>
		<div class="font_size12 color_9 mgb10 mgt10">
            <span class="tag hidden">优享试</span>剩余{pigcms{$goods['stock_num']-$goods['sell_count']}份
            <if condition="$is_leader eq 1 && ($leader_give_money gt 0 || $increase_price gt 0)">
                <span class="tag fr" style="<if condition='$config["mobile_head_color"]'>border-color: {pigcms{$config["mobile_head_color"]}; color: {pigcms{$config["mobile_head_color"]};</if> ">
                    <span>预计获得佣金</span>
                    <if condition="$leader_give_money gt 0">{pigcms{$config['Currency_symbol']}{pigcms{$leader_give_money|floatval}</if><if condition="$increase_price gt 0">+{pigcms{$config['Currency_symbol']}{pigcms{$increase_price}(加价)</if>
                </span>
            </if>
        </div>

		<if condition="$goods['open_time'] egt 1 && $goods['village_group_type'] neq 1">
			<div class="progress">
				<div class="rel" style="width: 95%;">
					<div class="flex flex_justify" >
						<div class="icon-box <if condition="time() egt $goods['start_time']">active</if>"><i class="icon iconfont">&#xe618;</i></div>
                        <div class="icon-box <if condition="time() egt $goods['end_time']">active</if>"><i class="icon iconfont">&#xe70e;</i></div>
                        <div class="icon-box <if condition="time() egt $goods['send_time']">active</if>"><i class="icon iconfont">&#xe873;</i></div>
                        <div class="icon-box <if condition="time() egt $goods['get_time']">active</if>"><i class="icon iconfont">&#xe620;</i></div>
                     </div>
                    <div class="line">
	<!-- width (16.66% - 8.33px) 50% (83.34% + 8.33px) 100% -->
                        <if condition="time() egt $goods['start_time'] AND time() lt $goods['end_time']"><div class="active" style="width:calc(16.66% - 8.33px);"></div>
                            <elseif condition="time() egt $goods['end_time'] AND time() lt $goods['send_time']" /><div class="active" style="width:50%;"></div>
                            <elseif condition="time() egt $goods['send_time'] AND time() lt $goods['get_time']" /><div class="active" style="width:calc(83.34% + 8.33px);"></div>
                            <elseif condition="time() egt $goods['get_time']" /><div class="active" style="width:100%;"></div>
                        </if>
                    </div>
                </div>
                <div class="flex flex_justify text">
                    <div id='start_time' data-start_time='{pigcms{$goods.start_time|date="m.d",###}'>{pigcms{$goods.start_time|date="m.d",###}开始</div>
                    <div id='end_time' data-end_time='{pigcms{$goods.end_time|date="m.d",###}'>{pigcms{$goods.end_time|date="m.d",###}结束</div>
                    <div id='send_time' data-send_time='{pigcms{$goods.send_time|date="m.d",###}'>{pigcms{$goods.send_time|date="m.d",###}发货</div>
                    <div id='get_time' data-get_time='{pigcms{$goods.get_time|date="m.d",###}'>{pigcms{$goods.get_time|date="m.d",###}前送达</div>
                </div>
            </div>
        </if>
        <div class="demand">
            <span><i class="icon iconfont" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};</if>" >&#xe611;</i>团员自提</span>
            <if condition="$goods.open_time eq 3">
                <span><i class="icon iconfont">&#xe611;</i>结团后当日达</span>
                <elseif condition="$goods.open_time eq 2" />
                <span><i class="icon iconfont">&#xe611;</i>结团后次日达</span>
            </if>
        </div>
    <div style="border-bottom: 1px solid #efefef;" class="color_9 font_size11 lh1-8 pd10_0 ">{pigcms{$goods.des}</div>
        <div class="delivery-desc <if condition="$goods['village_group_type'] neq 1">hide</if>">
            <div class="font_size14">配送说明</div>
            <div class="detail-box">
                <div class="flex flex-align line">
                    <span class="tip">配送频次</span>
                    <span class="txt">
                        <if condition="$cycle_info['cycle_type'] eq 1">每日一期<elseif condition="$cycle_info['cycle_type'] eq 2"/>每周一期<else/>每月一期</if>，共{pigcms{$cycle_info['delivery_periods']}期
                    </span>
                </div>
                <div class="flex flex-align line">
                    <span class="tip">配送顺延限制</span>
                    <span class="txt">
                        <if condition="$cycle_info['is_limit_delay'] neq 1">不限制次数<else/>限制{pigcms{$cycle_info['limit_delay_num']}次</if>
                    </span>
                </div>
                <div class="flex flex-align line">
                    <span class="tip">最近配送日期</span>
                    <if condition="$cycle_info['limit_days'] gt 0">
                        <span class="txt">需在最近配送日期前{pigcms{$cycle_info['limit_days']}天，且{pigcms{$cycle_info['limit_time']}点前下单支付</span>
                        <else/>
                        <span class="txt">需在最近配送日期当天，且{pigcms{$cycle_info['limit_time']}点前下单支付</span>
                    </if>
                </div>
            </div>
        </div>
    </div>
</div>
<if condition="$reply['reply_count']">
	<div class="box reply-box">
		<div class="title" style="<if condition='$config["mobile_head_color"]'>border-left-color: {pigcms{$config["mobile_head_color"]};</if>" >商品评价</div>
		<div class="ks-clear reply">
			<div class="left number-box">
				<div>满意度</div>
				<div class="red" style='<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};</if>'>{pigcms{$reply['satisfaction']}%</div>
			</div>
			<div class="left progress-box">
				<div>
					<span class="item">非常好</span><span class="progress-wrapper"><span class="progress" style="width:{pigcms{$reply['percent_3']}%; <if condition='$config["mobile_head_color"]'>background: {pigcms{$config["mobile_head_color"]};</if> "></span></span>
				</div>
				<div>
					<span class="item">好</span><span class="progress-wrapper"><span class="progress" style="width:{pigcms{$reply['percent_2']}%; <if condition='$config["mobile_head_color"]'>background: {pigcms{$config["mobile_head_color"]};</if>"></span></span>
				</div>
				<div>
					<span class="item">一般</span><span class="progress-wrapper"><span class="progress" style="width:{pigcms{$reply['percent_1']}%; <if condition='$config["mobile_head_color"]'>background: {pigcms{$config["mobile_head_color"]};</if>"></span></span>
				</div>
			</div>

		</div>
	</div>
</if>

<if condition="$all_count gt 0">
<div class="box avatar-box">
	<div class="title" style='<if condition='$config["mobile_head_color"]'>border-left-color: {pigcms{$config["mobile_head_color"]};</if>'>参团成员{pigcms{$all_count|intval}人</div>
	<div class="ks-clear avatar">
		<volist name="buyer_list" id="vo" offset="0" length="9">
			<div class="item"><img src="{pigcms{$vo.avatar}"></div>
		</volist>
		<if condition="count($buyer_list) gt 9">
			<div class="item"><div><i class="icon iconfont font_size20">&#xe642;</i></div></div>
		</if>
	</div>
</div>
</if>


<div class="box address-box">
	<div class="title"  style='<if condition='$config["mobile_head_color"]'>border-left-color: {pigcms{$config["mobile_head_color"]};</if>'>
		<span>取货地址</span>
		<span class="fr color_9">{pigcms{$header.pick_address.name}</span>
	</div>
	<div class="leader-info">
		<div class="mgb10">
			<if condition="$header['avatar']">
				<img class="avatar" src="{pigcms{$header.avatar}">
				<else/>
				<img class="avatar" src="{pigcms{$static_path}images/new_my/pic-default.png">
			</if>
			<span class="font_size16 mgr15">{pigcms{$header.name}</span>

			<if condition="!$config['village_group_hide_detail']">
				<a href="tel:{pigcms{$header.phone}"><span><i class="icon iconfont mgr5" id='icon-phone' style="vertical-align: top;">&#xe6dd;</i>{pigcms{$header.phone}</span></a>
			</if>
		</div>
		<div>{pigcms{$header.pick_address.pick_addr}</div>
	</div>
</div>

<div class="box detail-box">
<div class="title"  style='<if condition='$config["mobile_head_color"]'>border-left-color: {pigcms{$config["mobile_head_color"]};</if>'>商品详情</div>
	<div class="detail">
		{pigcms{$goods.content|html_entity_decode}
	</div>
	<div class="support">本商品由<span class="name"><if condition="$goods.own_sell eq 0 ">{pigcms{$merchant.name}<else />{pigcms{$config.village_group_productor_info}</if></span>提供。</div>
</div>

<!-- 分期购只显示首页和直接购买，直接购买按钮设置样式width:85%,隐藏class='hidden' -->
<div class="flex btn-box goodsItem price-box" data-price="{pigcms{$goods.price}" data-max_num="{pigcms{$goods['stock_num']-$goods['sell_count']}">
	<div class="home">
		<a href="{pigcms{:U('index')}&leader_id={pigcms{$_SESSION['leader_id']}" id="goIndex">
			<div><i class="icon iconfont" style='<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};</if>'>&#xe908;</i></div>
			<div class="text" style='<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};</if>'>首页</div>
		</a>
	</div>
	<div class="cart <if condition="$goods['village_group_type'] eq 1">hidden</if>">
		<a href="{pigcms{:U('cart')}&leader_id={pigcms{$_SESSION['leader_id']}">
			<div class="rel">
				<i class="icon iconfont color_6">&#xe628;</i>
				<span class="num goods_num"  style='<if condition='$config["mobile_head_color"]'>background-color: {pigcms{$config["mobile_head_color"]};</if>'>0</span>
			</div>
			<div class="text color_9">购物车</div>
		</a>
	</div>
	<div class="operation" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};</if><if condition='$is_leader AND $goods_bind_area["is_add_price"]'>display: block;</if>">
		<a href="javascript:;">
			<div class="rel">
				<i class="icon iconfont color_6">&#xe61c;</i>
			</div>
			<div class="text color_9">加价</div>
		</a>
	</div>
	<div class="add <if condition="$goods['village_group_type'] eq 1">hidden</if> <if condition="($goods['stock_num']-$goods['sell_count']) elt 0 OR ($goods['end_time'] gt 0 AND ($goods['end_time']+86400 lt time() OR $goods['start_time'] gt time()))">active<else/>plus</if>" data-goods_id="{pigcms{$goods.goods_id}" data-sell_count="{pigcms{$goods.sell_count}" 
	style="<if condition='$config["mobile_head_color"]'>background-color: #454452;</if> <if condition="($goods['stock_num']-$goods['sell_count']) elt 0 || $goods['end_time']+86400 lt time() || $goods['start_time'] gt time()">background-color:#c0c0c0;</if>"
	>加入购物车
</div>
	<div class="add <if condition="$goods['village_group_type'] eq 1">hidden</if> <if condition="$no_can_buy">active<else/>plus</if>" data-goods_id="{pigcms{$goods.goods_id}" data-sell_count="{pigcms{$goods.sell_count}" style="<if condition='$config["mobile_head_color"]'>background-color: #454452;</if> <if condition="$no_can_buy">background-color:#c0c0c0;</if>">加入购物车</div>
	<div class="number hidden" >0</div>
	<div data-village_group_type="{pigcms{$goods['village_group_type']}" style="<if condition='$config["mobile_head_color"]'>background-color: {pigcms{$config["mobile_head_color"]}; </if>
	<if condition="($goods['stock_num']-$goods['sell_count']) elt 0 ||  $goods['end_time']+86400 lt time() || $goods['start_time'] gt time()">background-color:#999;</if> 
	<if condition="$goods['village_group_type'] eq 1 && $is_leader AND $goods_bind_area['is_add_price']">width:70%; <elseif condition="$goods['village_group_type'] eq 1"/>width:85%; </if>"  class="buy <if condition="($goods['stock_num']-$goods['sell_count']) elt 0 OR ($goods['end_time'] gt 0 AND ($goods['end_time']+86400 lt time() OR $goods['start_time'] gt time() ))">active<else />can_buy</if>">直接购买</div>
	<div data-village_group_type="{pigcms{$goods['village_group_type']}" style="<if condition='$config["mobile_head_color"]'>background-color: {pigcms{$config["mobile_head_color"]}; </if><if condition="($goods['stock_num']-$goods['sell_count']) elt 0 ||  $goods['end_time']+86400 lt time() || $goods['start_time'] gt time()">background-color:#999;</if><if condition="$goods['village_group_type'] eq 1 && $is_leader AND $goods_bind_area['is_add_price']">width:70%; <elseif condition="$goods['village_group_type'] eq 1"/>width:85%; </if>"  class="buy <if condition="$no_can_buy">active<else />can_buy</if>"><if condition="$no_can_buy">已售罄<else/>直接购买 </if></div>
</div>
 

<!-- 分享蒙层 -->
<div class="shareMaskCommon"><img src="{pigcms{$static_path}villagegroup/images/shareMask.png"></div>
<div  id='addPrice'    >


<div class="mgt15" style="padding: 0px 10px 10px 10px;">
		<p class="rel">团长加价<span class='closePrice'>x</span></p>
		<div class="mgt10">团长加价即团长可以给每个商品设置一定的涨价空间</div>
		<div  class="mgt10 font_size18" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};<else/>color:#fe3112</if>">加价范围：
			<span class="font_b">
				<span class='min'>{pigcms{$goods_bind_area['min_price']}</span>-<span class='max'>{pigcms{$goods_bind_area['max_price']}</span>
			</span>
		</div>
	</div>
	<div class="mgt10" style='margin: auto;  width: 80%;'>
		<div class='flex' >
			<button class=' del'>-</button>
			<input type="text" class='flex_one priceExit' value="{pigcms{$increase_price}" id="increase_price" name="increase_price">
			<button class=' add'>+</button>
		</div>
	</div>
	<div class="flex flex-align add-price-btn">
		<button class='btn clear_add_price' style="background:#ADADAD;">重置</button>
		<button class='addPrice_btn btn' style="<if condition='$config["mobile_head_color"]'>background: {pigcms{$config["mobile_head_color"]};<else/>background:#fe3112</if>">修改</button>
	</div>
	
</div>
</div>
<div class="coupon_mask cursor" style="display: none;"></div>

<!-- 分期购弹窗 -->
<div class="instalments-box off">
	<div class="detail">
		<div class="flex goods-desc">
			<div class="img"><img src="{pigcms{$goods.image}"></div>
			<div class="rel">
				<div class="name">{pigcms{$goods.name}</div>
				<div>
                    <span class="red">{pigcms{$config['Currency_symbol']}</span>
                    <span class="red font_size20">
					{pigcms{$goods.price|floatval}
                    <if condition="$increase_price gt 0 AND $is_leader">
                            +{pigcms{$increase_price}
                    </if>
                    </span>/份 <span class="mgl5">剩余{pigcms{$goods['stock_num']-$goods['sell_count']}份</span></div>
				<i class="icon iconfont cancel">&#xe679;</i>
			</div>
		</div>
		<div class="pd10 bold">选择配送日<span class="red">(<if condition="$cycle_info['cycle_type'] eq 1">每日一期<elseif condition="$cycle_info['cycle_type'] eq 2"/>每周一期<else/>每月一期</if>，共{pigcms{$cycle_info['delivery_periods']}期)</span></div>
		<div class="flex flex-wrap flex-align select-time">
            <if condition="$cycle_info['cycle_type'] eq 1">
                <span class="active" data-val="1">每日</span>
                <elseif condition="$cycle_info['cycle_type'] eq 2"/>
                <volist name="cycle_info['delivery_date_arr']" id="item">
                    <span data-val="{pigcms{$key}">{pigcms{$item}</span>
                </volist>
                <else/>
                <volist name="cycle_info['delivery_date_arr']" id="item">
                    <span data-val="{pigcms{$item}" <if condition="$key eq 0">class="active"</if>>{pigcms{$cycle_info['date_msg'][$item]}</span>
                </volist>
            </if>
		</div>
        <div class="red pdl10 pdr10">预计最早配送时间：<span class="lately_cycle_time">{pigcms{$cycle_info.first_delivery_date}</span></div>
		<div class="bottom-box">
			<div class="flex flex_justify "> 
				<span>数量</span>
				<div class="calc-box" data-max_num="{pigcms{$goods['stock_num']-$goods['sell_count']}">
					<i class="icon iconfont cycle_minus" data-end_time='{pigcms{$goods.end_time}' data-goods_id="{pigcms{$goods.goods_id}" data-sell_count="{pigcms{$goods.sell_count}">&#xe60c;</i>
					<span class="pdl10 pdr10 number" id="cycle_good_{pigcms{$goods.goods_id}">1</span>
					<i class="icon iconfont cycle_plus" data-end_time='{pigcms{$goods.end_time}' data-goods_id="{pigcms{$goods.goods_id}" data-sell_count="{pigcms{$goods.sell_count}">&#xe60d;</i>
				</div>
			</div>
			<div><button class="can_buy_next">下一步</button></div>
		</div>
	</div>
</div>

<script>
	window.shareData = {
		"moduleName":"Goods",
		"moduleID":"0",
		"imgUrl": "{pigcms{$config.site_url}/village_group/goodswx_{pigcms{$_GET['leader_id']}_{pigcms{$_GET['goods_id']}",
		"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('goods',array('goods_id'=>$_GET['goods_id'],'leader_id'=>$_SESSION['leader_id']))}",
		"tTitle": "{pigcms{$goods.price|floatval}元【{pigcms{$goods.name}】",
		"tContent": "{pigcms{$header.pick_address.name}-【现在下单<if condition="$goods.open_time eq 0">次日达<else />{pigcms{$goods.get_time|date="m.d",###}号前送达</if>】，点击查看更多优惠！",
		"wxapp_tTitle": "【{pigcms{$header.pick_address.name}】{pigcms{$config["Currency_symbol"]}{pigcms{$goods.price}，{pigcms{$goods.name|trim=###}",
		"wxapp_tTitle2": "{pigcms{$goods.name|trim=###}",
		"wxapp_Pic": "{pigcms{$config.site_url}/village_group/goods_{pigcms{$_GET['leader_id']}_{pigcms{$_GET['goods_id']}",
		"wxapp_Url": "/pages/index/index?redirect=webview&webview_url={pigcms{:urlencode(U('goods',array('goods_id'=>$_GET['goods_id'],'leader_id'=>$_SESSION['leader_id'],'openid'=>$_SESSION['openid'])))}&webview_title={pigcms{:urlencode($config['village_group_alias'])}",
		"now_gooods_image":"{pigcms{$goods.image}",
	};
</script>
</script>
{pigcms{$shareScript}
<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>

<!-- weui -->
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/weui.min.css"/>
<script src="{pigcms{$static_path}villagegroup/js/weui.min.js"></script>
<!-- layer -->
<script src="{pigcms{$static_path}mall/js/cate_common.js"></script>
<script src="{pigcms{$static_path}mall/js/cate_complite.js" type="text/javascript" ></script>
<script type="text/javascript" src="{pigcms{$static_path}mall/js/swiper.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/fastclick.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}villagegroup/js/base.js?t={pigcms{:time()}" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
<script type="text/javascript">
	var get_cart_url="{pigcms{:U('ajax_cart_get')}";
	var save_cart_url="{pigcms{:U('ajax_cart_save')}";
	var save_group_order="{pigcms{:U('save_village_group_order')}";
	var all_get_num=0,all_get_money=0;
	var all_money = 0,num=0,cart_cookie='',goodsCart = {},can_pay = true;
	var start_id = '{pigcms{$_SESSION['leader_id']}';
	var goods_id = '{pigcms{$_GET['goods_id']}';
	var from_index = {pigcms{$_GET['from_index']|intval=###};
	var goodCard_tmp = {};
	goodCard_tmp[goods_id] = {'num':0,'good_price':0};
    var loading_first = '';
    var end_time_info = parseInt("{pigcms{$goods['end_time']}")+86400;
    var start_time_info = parseInt("{pigcms{$goods['start_time']}");
    var now_time = parseInt("{pigcms{:time()}");




	$(function(){
        if(!start_id){
            layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:'没有选择团长',btn:['好的'],shadeClose:false,yes:function(){
                window.location.href= '{pigcms{:U('leader')}';
            }});
        }
        loading_first = weui.loading('加载中');
		$.cookie('goods_leader_id',JSON.stringify(start_id));



        cart_cookie = $.cookie('cart_cookie'+start_id);
        cart_cookie_goods = $.cookie('cart_cookie'+start_id+'_'+goods_id);
        if(!cart_cookie){
            $.cookie('cart_cookie'+start_id,randomString(32),{expires:1,path:'/'});
            cart_cookie = $.cookie('cart_cookie'+start_id);
        }
        if(!cart_cookie_goods){
            $.cookie('cart_cookie'+start_id+'_'+goods_id,randomString(32),{expires:1,path:'/'});
            cart_cookie_goods = $.cookie('cart_cookie'+start_id+'_'+goods_id);
        }
        $.post(get_cart_url,{cart_cookie:cart_cookie,start_id:start_id},function(data){
            if(data.status==1){
                var goods_detail = data.info;
                var cart_html = '';

                for(var i in goods_detail ){
                    goodsCart[goods_detail[i].goods_id] = {'num':goods_detail[i].count,'good_price':goods_detail[i].productParam.good_price};

                    all_get_num +=parseInt(goods_detail[i].count);
                    all_get_money +=parseFloat(goods_detail[i].productParam.good_price)*parseInt(goods_detail[i].count);
                }
                if(goodsCart[goods_id]){

                    $('.number').html(goodsCart[goods_id].num)
                }
                goodsCart['all'] = {'all_money':all_get_money,'all_num':all_get_num,'cate_num':goods_detail.length};
                $('.num').html(all_get_num)
                num = all_get_num

            }

        });
        $.post(get_cart_url,{cart_cookie:cart_cookie_goods,start_id:start_id,goods_id:goods_id},function(data){
            if(data.status==1){
                var goods_detail = data.info;
                var cart_html = '';
                console.log(goods_detail)
                if(goods_detail.length>0){

                    goodCard_tmp[goods_detail[0].goods_id] = {'num':goods_detail[0].count,'good_price':goods_detail[0].productParam.good_price};
                }
            }

        });

        loading_first.hide();

		$('#goodsImgBox .img').each(function(index, item){
			if (index == 0) {
				var img_url = $(item).attr('src');
				getImageWidth(img_url, function(w, h){
					var now_w = $(window).width() - 50;
					var now_h = now_w * h / w ; 
					if (now_h > now_w) {
						now_h = now_w;
					}
					$('.goods-box .img-box #goodsImgBox').css('height', now_h);
				})
			}
		})

		// 获取图片真实高度
		function getImageWidth(url,callback){
		    var img = new Image();
		    img.src = url;
		    // 如果图片被缓存，则直接返回缓存数据
		    if(img.complete){
		        callback(img.width, img.height);
		    }else{
		        img.onload = function(){
		            callback(img.width, img.height);
		    	}
			}
		}

		$(document).on('click', '.instalments-box .select-time span', function(){
			$('.instalments-box .select-time span').removeClass('active');
			$(this).addClass('active');
			var val = $(this).data('val');
			console.log('val',val);
            $.post("{pigcms{:U('lately_cycle_time')}",{goods_id:goods_id,time:val},function(data){
                console.log('lately_cycle_time',data);
                if (data.lately_cycle_time) {
                    $('.lately_cycle_time').html(data.lately_cycle_time);
                }
            },'json')
		});

		$(document).on('click', '.instalments-box .cancel', function(){
			$('.instalments-box').addClass('off')
		});
        // can_buy_next

        $('.can_buy_next').click(function(){
            console.log("下一步")

			var num = $("#cycle_good_"+goods_id).html();
            var time = $('.select-time span.active').data('val');
            var lately_cycle_time = $('.lately_cycle_time').html();
            var cycle_type = "{pigcms{$cycle_info['cycle_type']}";
            console.log('num',num);
            console.log('time',time);
            console.log('lately_cycle_time',lately_cycle_time);
            console.log('cycle_type',cycle_type);
            goodCard_tmp[goods_id] = {'num':num,'good_price':'{pigcms{$goods.price}'};

            if(!start_id){
                layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:'没有选择团长',btn:['好的'],shadeClose:false,yes:function(){
                        window.location.href= '{pigcms{:U('leader')}';
                    }});
            }else{
                $.post(save_cart_url,{start_id:start_id,cart_cookie:cart_cookie_goods,goodsCart:goodCard_tmp,single_buy:1},function(data){
                    if(data.status){
                        $.post(save_group_order,{start_id:start_id,cart_cookie:cart_cookie_goods,goodsCart:goodCard_tmp,time:time, lately_cycle_time:lately_cycle_time, cycle_type:cycle_type,goods_id:goods_id,single_buy:1},function(data){

                            if(data.status==1){
                                // layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:data.info,btn:['好的'],shadeClose:false,yes:function(){
                                window.location.href=data.url
                                // }});
                            }else if(data.status==0){

                                layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:data.info,btn:['好的'],shadeClose:false,yes:function(){
                                        if(data.url!=''){
                                            window.location.href= data.url;
                                        }
                                        layer.closeAll();
                                    }});

                            }
                        },'json');
                    }else{
                        // layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:data.info,btn:['好的'],shadeClose:false,yes:function(){
                        // 	layer.closeAll();
                        // }});

                        layer.open({
                            content: data.info
                            ,shade: false
                            ,className:'sell-out-tip'
                            ,style: 'background-color:rgba(0, 0, 0, .6);display:inline-block;color:#fff;width:auto;height:auto;padding:10px;'
                            ,time: 2.5
                        });

                    }

                },'json');
            }
        });
        if (motify.checkApp() && !motify.checkWeixin) {
			if(motify.checkAndroid()){
				window.lifepasslogin.shareLifePass(window.shareData.tTitle,window.shareData.tContent,window.shareData.imgUrl,window.shareData.sendFriendLink);
			}else {
				iosCommon.iosFunction('shareLifePass', {name:'shareLifePass', imgUrl: window.shareData.imgUrl, sendFriendLink:window.shareData.sendFriendLink, tTitle:window.shareData.tTitle, tContent:window.shareData.tContent });
			}
		}

		// 商品图片轮播
		$('.img-num .total').html($('#goodsImgBox .swiper-slide').length)
		var imgSlider = new Swiper('#goodsImgBox',{
			slidesPerView: 'auto',
			loopedSlides:1,
			pagination: '#goodsImgBoxx .swiper-pagination',
			onSlideChangeEnd: function (swiper) {
				$('.img-num .now').html(swiper.activeIndex + 1)
			}
		});
		var reg=/^[0-9]*$/;
		$(document).bind("input",".priceExit",function(){
			if (!reg.test($(".priceExit").val())) {
				$(".priceAdd").hide().find(".newMoney").html("")
			}else{
				if($(".priceExit").val()>0){
					if($(".priceExit").val()>=$(".max").html()*1){

						$(".priceExit").val(parseFloat($(".max").html()).toFixed(2));
						$(".priceAdd").show().find(".newMoney").html($(".max").html()*1);
					}else if($(".priceExit").val()<=$(".min").html()*1){
						$(".priceExit").val($(".min").html()*1)
						$(".priceAdd").show().find(".newMoney").html($(".max").html()*1)
					}else{
						$(".priceAdd").show().find(".newMoney").html($(".priceExit").val())
					}
				}else{
					$(".priceAdd").hide().find(".newMoney").html("")
				}
			}
		})
		$(document).on("click",".operation a",function(){
			$("#addPrice").show()

			$(".coupon_mask").show()
		})
		$(document).on("click",".addPrice_btn",function(){

			$("#addPrice").hide();
			$(".coupon_mask").hide();
			var increase_price = $('input[name=increase_price]').val();
			$.post("{pigcms{:U('increase_price')}",{goods_id:goods_id,increase_price:increase_price},function(data){
				console.log(data.msg)
				if(data.code==0){
					layer.open({
						title:['提示','background-color:#8DCE16;color:#fff;'],
						content:data.msg,
						btn:['确定'],
						shadeClose:false,
						yes:function(){
							window.location.reload();
						layer.closeAll();
					}});
				}else{
					layer.open({
						title:['提示','background-color:#8DCE16;color:#fff;'],
						content:data.msg,
						btn:['关闭'],
						shadeClose:false,
					});
				}
			},'json')
		})
		// 清除加价
		$(document).on("click",".clear_add_price",function(){
			var increase_price = $('input[name=increase_price]').val();

			var clear_add_price = layer.open({
				title:['提示','background-color:#8DCE16;color:#fff;'],
				content:'是否清除加价',
				btn:['确定','取消'],
				shadeClose:false,
				yes:function(){
					$.post("{pigcms{:U('increase_price')}",{goods_id:goods_id,is_clear:1},function(data){
						if(data.code==0){
							layer.open({
								title:['提示','background-color:#8DCE16;color:#fff;'],
								content:data.msg,
								btn:['确定'],
								shadeClose:false,
								yes:function(){
									window.location.reload();
									layer.closeAll();
							}});
						}else{
							layer.open({
								title:['提示','background-color:#8DCE16;color:#fff;'],
								content:data.msg,
								btn:['关闭'],
								shadeClose:false,
							});
						}
					},'json')
				},
                no:function(){
					layer.close(clear_add_price)
				}
			});
		})

		$(document).on("click",".closePrice",function(){
			$("#addPrice").hide();
			$(".coupon_mask").hide();
		})

		$(document).on("click","#addPrice .add",function(){
			var p = parseFloat($(".priceExit").val());
			if(p=='' || isNaN(p)){
				$(".priceExit").val(0)
				p = '0.00';
			}

			if(p>=parseFloat($(".max").html()).toFixed(2)){
				$(".priceExit").val(parseFloat($(".max").html()).toFixed(2));
			} else{
				p++;
				p = parseFloat(p.toFixed(2));
				$(".priceExit").val(p)
			}
			
		});
		$(document).on("click","#addPrice .del",function(){
			var p = parseFloat($(".priceExit").val());
			if(p=='' || isNaN(p)){
				$(".priceExit").val(0)
				p = 0;
			}
			
			if(p<=parseFloat($(".min").html()).toFixed(2)){
				$(".priceExit").val(parseFloat($(".min").html()).toFixed(2));
			} else{
				p--;
				p = parseFloat(p.toFixed(2));
				$(".priceExit").val(p)
			}
			

		})


		/*预览图片*/
		$("body").on("click",".pics img",function(){
			var album_array = [];
			var  index=parseInt($(this).attr('data-index')) - 1;
			$(this).closest(".pics").find('img').each(function(n,v){
				var pics=$(this).data('pics');
				if(pics){
					album_array[n]=pics;
				}
			});
			var pb1 = APP.Photos({
				items:album_array,
				onSlideChange: function(index) {
					console.log(this, index);
				},
				onOpen: function() {
					console.log("onOpen", this);
				},
				onClose: function() {
					console.log("onClose", this);
					$(".weui-photo-browser-modal").remove()
				}
			});
			pb1.open(index);
		});
		$('#goIndex').click(function(){
			if(from_index == 1){
				if(typeof wx !=='undefined' && window.__wxjs_environment === 'miniprogram'){
					wx.miniProgram.navigateBack();
				}else{
					history.go(-1);
				}
				return false;
			}
		});

		

		$('.can_buy').click(function(){
            var village_group_type = $(this).data('village_group_type');
            var goods_village_group_type = "{pigcms{$goods['village_group_type']}";
            console.log('village_group_type',village_group_type);
            console.log('goods_village_group_type',goods_village_group_type);
            if (village_group_type==1 || goods_village_group_type==1) {
                $('.instalments-box').removeClass('off');
                return;
            }

			if(typeof(goodCard_tmp[goods_id].num)!=undefined && goodCard_tmp[goods_id].num>0){
				// goodCard_tmp[goods_id] = {'num':goodCard_tmp[goods_id].num+1,'good_price':'{pigcms{$goods.price}'}
				// 默认取数量1 不做累加
				goodCard_tmp[goods_id] = {'num':1,'good_price':'{pigcms{$goods.price}'}
			}else{
				goodCard_tmp[goods_id] = {'num':1,'good_price':'{pigcms{$goods.price}'}
			}

			if(!start_id){
				layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:'没有选择团长',btn:['好的'],shadeClose:false,yes:function(){
						window.location.href= '{pigcms{:U('leader')}';
					}});
			}else{


				$.post(save_cart_url,{start_id:start_id,cart_cookie:cart_cookie_goods,goodsCart:goodCard_tmp,single_buy:1},function(data){
					if(data.status){
						$.post(save_group_order,{start_id:start_id,cart_cookie:cart_cookie_goods,goodsCart:goodCard_tmp},function(data){

							if(data.status==1){
								// layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:data.info,btn:['好的'],shadeClose:false,yes:function(){
								window.location.href=data.url
								// }});
							}else if(data.status==0){

								layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:data.info,btn:['好的'],shadeClose:false,yes:function(){
										if(data.url!=''){
											window.location.href= data.url;
										}
										layer.closeAll();
									}});

							}
						},'json');
					}else{
						// layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:data.info,btn:['好的'],shadeClose:false,yes:function(){
						// 	layer.closeAll();
						// }});

						layer.open({
							content: data.info
							,shade: false
							,className:'sell-out-tip'
							,style: 'background-color:rgba(0, 0, 0, .6);display:inline-block;color:#fff;width:auto;height:auto;padding:10px;'
							,time: 2.5
						});

					}

				},'json');
			}
		});


		$(document).on('click', '.commonShare', function(){
			if(typeof wx != 'undefined'){
				wx.miniProgram.getEnv(function(res) {
					if (res.miniprogram ) {
						//小程序中
						wx.miniProgram.navigateTo({
							url:'/pages/index/share?title='+encodeURIComponent(window.shareData.wxapp_tTitle2)+'&shareTitle='+encodeURIComponent(window.shareData.wxapp_tTitle)+
									'&shareImg='+encodeURIComponent(window.shareData.imgUrl)+'&sharePath='+encodeURIComponent(window.shareData.wxapp_Url)+'&poster='+encodeURIComponent(window.shareData.wxapp_Pic)+'&bg='+encodeURIComponent(window.shareData.now_gooods_image)
						})
					}else{
						//微信端
						$('.shareMaskCommon').show()
					}
				});
			} else if (motify.checkApp()) {
				if(motify.checkAndroid()){
					window.lifepasslogin.shareWeChat(window.shareData.tTitle,window.shareData.tContent,window.shareData.imgUrl,window.shareData.sendFriendLink);
				}else {
					iosCommon.iosFunction('shareWeChat', {name:'shareWeChat', imgUrl: window.shareData.imgUrl, sendFriendLink:window.shareData.sendFriendLink, tTitle:window.shareData.tTitle, tContent:window.shareData.tContent });
				}
			}

		})

		$(document).on('click', '.shareMaskCommon', function(){
			$('.shareMaskCommon').hide()
		})

		// 预售时间进度条
        console.log('end_time_info',end_time_info);
        console.log('start_time_info',start_time_info);
        console.log('now_time',now_time);
        if ((end_time_info>now_time || start_time_info>now_time) && end_time_info>0) {
            if (start_time_info > now_time) {
                console.log('start_time_info1',start_time_info);
                addTimer('time',start_time_info);
            } else {
                console.log('end_time_info1',end_time_info);
                addTimer('time',end_time_info);
            }
        }
	});




	var addTimer = function(){
		var list = [],interval;
		return function(id,timeStamp){
			if(!interval){
				interval = setInterval(go,900);
			}

			if (document.getElementById('day')) {
				list.push({
					day:document.getElementById('day'),
					hour:document.getElementById('hour'),
					min:document.getElementById('min'),
					sec:document.getElementById('sec'),
					time:timeStamp
				});
			} else {
				clearInterval(interval)
			}

		}

		function go(){
			for (var i = 0; i < list.length; i++) {
				if (!list[i].time) {
					list.splice(i--, 1);
				}

				time_obj = changeTimeStamp(list[i].time);
				if( time_obj.day*1 > 0) {
					list[i].day.innerHTML = time_obj.day;
				} else {
					$('#time .day-box').hide()
				}
				list[i].hour.innerHTML = time_obj.hour;
				list[i].min.innerHTML = time_obj.min;
				list[i].sec.innerHTML = time_obj.sec;


			}
		}

		function changeTimeStamp(timeStamp){
			var distancetime = new Date(timeStamp*1000).getTime() - new Date().getTime();
			if(distancetime > 0){
				// var ms = Math.floor(distancetime%1000);
				var sec = Math.floor(distancetime/1000%60);
				var min = Math.floor(distancetime/1000/60%60);
				var hour =Math.floor(distancetime/1000/60/60%24);
				var day =Math.floor(distancetime/1000/60/60/24);
				if(sec<10){
					sec = "0"+ sec;
				}
				if(min<10){
					min = "0"+ min;
				}
				if(hour<10){
					hour = "0"+ hour;
				}

				return {'day':day,'hour':hour,'min':min,'sec':sec};
			}else{
				// window.location.reload();
			}
		}
	}();


</script>



</body>
</html>
