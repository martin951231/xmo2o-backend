<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>订单确认</title>

<!--        <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/mobiscroll_min.css" media="all">-->
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/villagegroup.css?t={pigcms{:time()}" media="all">

		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/index.css?t={pigcms{:time()}"/>
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/confirm_order.css" media="all"/>
	</head>
    <style>
        .order-page .line_discount {
            height: 35px;
            line-height: 35px;
            padding: 0 20px 0 15px;
            background: #fff;
            margin-bottom: 5px;
        }
        .more {
            position: relative;
            width: 100%;
            box-sizing: border-box;
        }
        .more:after {
            position: absolute;
            right: .5rem;
            top: 50%;
            display: block;
            content: '';
            width: .6em;
            height: .6em;
            border-left: .04rem solid #999;
            border-bottom: .04rem solid #999;
            -webkit-transform: translateY(-50%) scaleY(0.7) rotateZ(-135deg);
            -moz-transform: translateY(-50%) scaleY(0.7) rotateZ(-135deg);
            -ms-transform: translateY(-50%) scaleY(0.7) rotateZ(-135deg);
        }
        input.mt[type=radio]:checked, input.mt[type=checkbox]:checked {
            background-color: #2bb2a3;
            border: 0;
            color: #fff;
        }
        input.mt[type=radio], input.mt[type=checkbox] {
            -webkit-appearance: none;
            width: 1.4rem;
            height: 1.4rem;
            margin: -.07rem 0;
            border-radius: 50%;
            border: .04rem solid #ddd8ce;
            text-align: center;
            vertical-align: middle;
            line-height: 1.2rem;
            outline: 0;
        }
        input.mt[type=radio]:checked:after, input.mt[type=checkbox]:checked:after {
            content: "✓";
            font-size: 1.34rem;
            font-family: base_icon;
        }
        .vipMoney{
            font-weight: bold;
            margin: 0px 10px 0px 5px;
            color:#777;
            position:relative;
        }
        .vipMoney:after{
            content: ' ';
            position: absolute;
            left: 0;
            right: 0;
            top: 49%;
            height: 1px;
            border-top: 2px solid #bbb;
        }
        .weui-cells {

            background-color: #fff;
            line-height: 2.47058824;

            overflow: hidden;
            position: relative;
        }
        .weui-cell {
            padding: 10px 15px;
            position: relative;
            display: -webkit-box;
            display: -webkit-flex;
            display: flex;
            -webkit-box-align: center;
            -webkit-align-items: center;
            align-items: center;
        }
        .weui-cell__bd {
            -webkit-box-flex: 1;
            -webkit-flex: 1;
            flex: 1;
        }
        .weui-cell_access .weui-cell__ft {
            padding-right: 13px;
            position: relative;
        }
        .weui-cell__ft {
            text-align: right;
            color: #999;
        }
        .weui-cell_access .weui-cell__ft:after {
            content: " ";
            display: inline-block;
            height: 6px;
            width: 6px;
            border-width: 2px 2px 0 0;
            border-color: #c8c8cd;
            border-style: solid;
            -webkit-transform: matrix(.71,.71,-.71,.71,0,0);
            transform: matrix(.71,.71,-.71,.71,0,0);
            position: relative;
            top: -2px;
            position: absolute;
            top: 50%;
            margin-top: -4px;
            right: 2px;
        }
        .weui-switch {
            -webkit-appearance: none;
            position: relative;
            width: 52px;
            height: 32px;
            border: 1px solid #dfdfdf;
            outline: 0;
            border-radius: 16px;
            box-sizing: border-box;
            background-color: #dfdfdf;
            -webkit-transition: background-color .1s,border .1s;
            transition: background-color .1s,border .1s;
        }
        .weui-cells_form input, .weui-cells_form label[for], .weui-cells_form textarea {
            -webkit-tap-highlight-color: rgba(0,0,0,0);
        }
         .weui-switch:checked {
            border-color: #fe3112;
            background-color: #fe3112;
        }



        .weui-switch-cp__box:before, .weui-switch:before {
            content: " ";
            position: absolute;
            top: 0;
            left: 0;
            width: 50px;
            height: 30px;
            border-radius: 15px;
            background-color: #fdfdfd;
            -webkit-transition: -webkit-transform .35s cubic-bezier(.45,1,.4,1);
            transition: -webkit-transform .35s cubic-bezier(.45,1,.4,1);
            transition: transform .35s cubic-bezier(.45,1,.4,1);
            transition: transform .35s cubic-bezier(.45,1,.4,1),-webkit-transform .35s cubic-bezier(.45,1,.4,1);
        }
        .weui-switch-cp__box:after, .weui-switch:after {
            content: " ";
            position: absolute;
            top: 0;
            left: 0;
            width: 30px;
            height: 30px;
            border-radius: 15px;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,.4);
            -webkit-transition: -webkit-transform .35s cubic-bezier(.4,.4,.25,1.35);
            transition: -webkit-transform .35s cubic-bezier(.4,.4,.25,1.35);
            transition: transform .35s cubic-bezier(.4,.4,.25,1.35);
            transition: transform .35s cubic-bezier(.4,.4,.25,1.35),-webkit-transform .35s cubic-bezier(.4,.4,.25,1.35);
        }
        .weui-switch-cp__input:checked~.weui-switch-cp__box:before, .weui-switch:checked:before {
            -webkit-transform: scale(0);
            transform: scale(0);
        }
        .weui-switch-cp__input:checked~.weui-switch-cp__box:after, .weui-switch:checked:after {
            -webkit-transform: translateX(20px);
            transform: translateX(20px);
        }
        .weui-cells_form .weui-cell__ft {
            font-size: 0;
        }
        .weui-cells:after, .weui-cells:before {
            content: " ";
            position: absolute;
            left: 0;
            right: 0;
            height: 1px;
            color: #e5e5e5;
            z-index: 2;
        }
        .weui-cells:after {
            bottom: 0;
            border-bottom: 1px solid #e5e5e5;
            -webkit-transform-origin: 0 100%;
            transform-origin: 0 100%;
            -webkit-transform: scaleY(.5);
            transform: scaleY(.5);
        }
        .box{margin:0 0 15px 0; border-width: 1px 0 0;}
        .box li{padding: 10px 15px; border-width: 0 0 1px; line-height: 24px;}
        .box li a{min-height: 24px;}
        /* .box li a > *{display: table-cell; vertical-align: middle;}
        .box li a span:first-child i{margin: 0 10px 0 2px; vertical-align: middle;} */
        /*.box li a strong{width: 100%;}*/
        .total-box .btn a{
            color:#fff;
        }

        .ico_arrow {
            width: 8px;
            height: 13px;
            background-position: -14px -25px;
        }

        .pay_box li a > span {
            width: 100%;
            text-align: right;
        }
        .box li a strong {
            width: 100%;
        }
        .box li a > * {
            display: table-cell;
            vertical-align: middle;
        }
        .pay_box li a > strong {
            width: auto;
            white-space: nowrap;
            padding-right: 10px;
        }
        .pay_box .ico_arrow {
            vertical-align: -2px;
            margin-left: 10px;
        }
        .pick_in_store {
            background: #FF7A3F;
            color: white !important;
        }

    </style>
	<body class="order-page">

        <if condition="$config['village_group_delivery_switch'] eq 1 && $order['village_group_type'] != 1">
            <div class="deliver_time_box">
                <div class="mask"></div>
                <div class="timer">
                    <div class="header">期望送达</div>
                    <div class="content">
                        <div class="day">
                            <volist name="dates" id="vo">
                                <div class="item <if condition="$i eq 1">active</if>" data-date="{pigcms{$vo.ymd}">{pigcms{$vo.show_date}</div>
                            </volist>
                        </div>
                        <div class="time" style="-webkit-overflow-scrolling:touch;">
                            <volist name="dates" id="vo">
                                <div class="item_box timer-{pigcms{$vo.ymd}" <if condition="$i neq 1">style="display:none;"</if>>
                                    <volist name="vo['date_list']" id="voo" key="k">
                                        <div class="item <if condition="$i eq 1 && $k eq 1">active</if>" data-show_date="{pigcms{$vo.show_date}" data-ymd="{pigcms{$vo.ymd}" data-time="{pigcms{$voo.hour_minute}" data-delivery_fee="{pigcms{$voo.delivery_fee_old}">{pigcms{$voo.hour_minute}<span class="price">({pigcms{$voo.delivery_fee_old}{pigcms{$config['Currency_txt']}配送费)</span></div>
                                    </volist>
                                </div>
                            </volist>
                        </div>
                    </div>
                </div>
            </div>
            <ul class="box deliver_pick">
                <li class="delivery_type">
                    <a class="ico">配送方式：</a>&nbsp;&nbsp;
                    <a class="btn_express pick_in_store" id="post_package">{pigcms{$config['deliver_name']}</a>
                    <a class="btn_express " id="pick_in_store">自提</a>
                </li>
                <li id="li_delivery">
                    <a href="{pigcms{:U('My/adress',array('buy_type' => 'village_group', 'current_id'=>$user_adress['adress_id'], 'order_id' => $order_id))}">
                        <strong>
                            <span id="showAddres">
                                <if condition="$user_adress['adress_id']">
                                    {pigcms{$user_adress['province_txt']} {pigcms{$user_adress['city_txt']} {pigcms{$user_adress['area_txt']} {pigcms{$user_adress['adress']} {pigcms{$user_adress['detail']}
                                    <else/>
                                    请点击添加送货地址
                                    <php>if($address_count > 0){</php>
                                        <br/>
                                        <span style="font-size: 8px;color: #bbb5b5">已有地址均不在配送范围内</span>
                                    <php>}</php>
                                </if>
                            </span><br>
                            <span id="showName">{pigcms{$user_adress['name']}</span>
                            <span id="showTel">{pigcms{$user_adress['phone']}</span>
                        </strong>
                        <div><i class="ico_arrow"></i></div>
                    </a>
                </li>
            </ul>
            <ul class="box pay_box deliver_time">
                <li id="show_arrive_time">
                    <a href="javascript:void(0);" id="timeBtn" class="time">
                        <strong>送达时间</strong>
                        <php>if (!empty($dates)) {</php>
                        <span id="arriveTime">{pigcms{$dates.0.show_date} {pigcms{$dates.0.date_list.0.hour_minute}</span>
                        <php> } else { </php>
                        <span id="arriveTime">抱歉,当前暂无可配送的预计送达时间</span>
                        <php> } </php>
                        <div><i class="ico_arrow"></i></div>
                    </a>
                </li>
            </ul>
        </if>

        <ul class="box pay_box deliver_time">
            <li>
                <a href="javascript:void(0);" id="remarkBtn" class="line_box">
                    <strong>订单备注</strong>
                    <span id="remarkTxt">点击添加订单备注</span>
                    <div><i class="ico_arrow"></i></div>
                </a>
            </li>
        </ul>

        <div class="addres_box" id="remarkBox">
            <ul>
                <li><textarea class="txt max" placeholder="请填写备注" id="userMark"></textarea></li>
                <li class="btns_wrap">
                    <span><a href="javascript:void(0);" class="comm_btn higher disabled" id="cancleRemark">取消</a></span>
                    <span><a href="javascript:void(0);" class="comm_btn higher" id="saveRemark">确认</a></span>
                </li>
            </ul>
        </div>
		<div class="address-box">
			<div class="mgb5">
				<span class="icon" style="<if condition='$config["mobile_head_color"]'>background: {pigcms{$config["mobile_head_color"]};</if>">团长</span>
				<span>{pigcms{$header.name}</span>
				<span class="font_size12 color_9 mgl5">{pigcms{$header.pick_address.name}</span>
			</div>
			<!-- 增加电话 -->
            <if condition="!$config['village_group_hide_detail']">
			    <a href="tel:{pigcms{$header.phone}"><p class="mgb5"><i class="icon iconfont" id='icon-phone'>&#xe6dd;</i>联系电话：{pigcms{$header.phone}</p></a>
            </if>
			<p><i class="icon iconfont" id='icon-location'>&#xe60b;</i>取货地址：{pigcms{$header.pick_address.pick_addr}</p>
		</div>

		<div class="goodsBox cart">
		
		<volist name="order.order_detail" id="vo">
			<div class="goodsItem">
				<div class="image">
					<img src="{pigcms{$vo.image}">
					<!-- 增加图标 -->
					<if condition="$vo['label']['type'] eq 1">
					<img class="icon small " src="{pigcms{$vo['label']['image']}">
					</if>
					
						
					<if condition="$vo['label']['type'] eq 0">
						<span class="icon icon-text small " style="background:linear-gradient(to bottom, {pigcms{$vo['label']['color']} , {pigcms{$vo['label']['color2']})">{pigcms{$vo['label']['name']}</span>
					</if>
				</div>

				<div class="info">
                    <div style='    display: flex;'>
                        <div class="name" style='flex:1'>{pigcms{$vo.name}</div>

                    </div>

					
						<div class="font_size12 color_9">取货时间：<if condition="$header.start_time neq '00:00:00'">{pigcms{$header.start_time|substr=0,-3}-{pigcms{$header.end_time|substr=0,-3}<else />08:00-20:00</if></div>


                    <if condition="$order['village_group_type'] eq 1">
                        <div class="tag future">周期购</div><span style="color: red;">&nbsp;&nbsp;<if condition="$order['cycle_type'] eq 1">每日一期<elseif condition="$order['cycle_type'] eq 2"/>每周一期<else/>每月一期</if>，共{pigcms{$order['delivery_periods']}期</span>
                    </if>
                    <if condition="$order['goods'][$vo['goods_id']]['open_time'] eq 1">
                        <if condition="$order['goods'][$vo['goods_id']]['cat_show_tag'] eq 1">
						<div class="tag future">预售</div>
                        </if>
                        {pigcms{$order['goods'][$vo['goods_id']]['get_time']|date="m.d",###}送达
					<elseif condition="$order['goods'][$vo['goods_id']]['open_time'] eq 3" />
					<div class="tag">
							当日达
					
						</div>
					<elseif condition="$order['goods'][$vo['goods_id']]['open_time'] eq 2" />
						<div class="tag">
							次日达
					
						</div>
					</if>
					<!-- <div class="tag future">预售</div> -->
					
					<div class="price-box">
                         <span>
                             <if condition="!empty($level_off_system) && $vo['is_discount'] gt 0">
                                 <span class=" mgt5" style="padding-left:3px">×{pigcms{$vo.num}</span>
                                 <span style='    ' class='vipMoney'>{pigcms{$config['Currency_symbol']}{pigcms{$vo.price|floatval}</span>
                                 <span style='    font-weight: bold;'>{pigcms{$config['Currency_symbol']}{pigcms{$vo.discount_price|floatval}</span>
                                 <span style='   color: #fff; background: #fe3112;padding: 1px 3px; border-radius: 5px; font-size: 12px; '>VIP</span>
                            <else/>
                                 <span class=" mgt5" style="padding-left:3px">×{pigcms{$vo.num}</span>
                                 <if condition="$vo['old_price'] gt $vo['price']">
                                 <span style='    ' class='vipMoney'>{pigcms{$config['Currency_symbol']}{pigcms{$vo.old_price|floatval}</span>
                                </if>
                                 <span style='    font-weight: bold;'>{pigcms{$config['Currency_symbol']}{pigcms{$vo.price|floatval}</span>
                             </if>
				       	  </span>
						<!--<span class="price" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};</if>">{pigcms{$config['Currency_symbol']}<span class="font_size20">{pigcms{$vo.price}</span></span>
						<span class="old-price">{pigcms{$config['Currency_symbol']}{pigcms{$vo.old_price|floatval}</span>-->


						
					</div>
				</div>
			</div>
           <!-- <if condition="!empty($level_off_system) && $is_discount gt 0">
            <div class="line_discount">
                <span>等级优惠</span>
                <span class="fr">
                    <if condition="$vo['level_info']['type'] eq 1">
                        <strong style="font-size:14px;color:#FF4907">折扣{pigcms{$vo['level_info']['vv']/10|floatval}折</strong>
                    <elseif condition="$vo['level_info']['type'] eq 2" />
                        <strong style="font-size:14px;color:#FF4907">每份立减{pigcms{$vo['level_info']['vv']|floatval}{pigcms{$config['Currency_txt']}</strong>
                    </if>
                </span>
            </div>
            </if>-->
		</volist>
	

		</div>


        <php>if($_GET['unsys_coupon']){</php>
                <a class="react" href="{pigcms{:U('My/select_card',($coupon_url?$coupon_url :$_GET))}&coupon_type=system" >
                    <div class="line_discount more more-weak">
                        <span>平台优惠券</span>
                        <span class="more-after fr">
                            不使用
                        </span>
                    </div>
                </a>
        <php>}else if(empty($system_coupon)){</php>
<!--            <a class="react">-->
<!--                <div class="line_discount more more-weak">-->
<!--                    <span>平台优惠券</span>-->
<!--                    <span class="more-after fr">-->
<!--                            无可用优惠券-->
<!--                        </span>-->
<!--                </div>-->
<!--            </a>-->
        <php> }else{ </php>
            <div class="weui-cells">
                <a class="weui-cell weui-cell_access"  href="{pigcms{:U('My/select_card',($coupon_url ? $coupon_url :$_GET))}&coupon_type=system" >
                    <div class="weui-cell__bd">
                        <p>平台优惠券</p>
                    </div>
                    <div class="weui-cell__ft" style="color:red;">
                        <php>if($system_coupon){
                            if($system_coupon['order_money']>0){
                        </php>
                        满{pigcms{$system_coupon.order_money}
                        <php>}</php>
                        <php>if($system_coupon['is_discount']==1){</php>
                        打{pigcms{$system_coupon.discount_value|floatval}折
                        <php>}else{</php>
                        减{pigcms{$system_coupon.discount}
                        <php>}</php>
                        <php> }else{ </php>
                        使用优惠券
                        <php> } </php>
                    </div>
                </a>
            </div>
        <php> } </php>

    <!--    <div class="line">
            <span>商品金额</span>
            <span class="fr">{pigcms{$config['Currency_symbol']}{pigcms{$order.total_money|floatval}</span>
        </div>-->


       <!-- <if condition="!empty($level_off_system) && $is_discount gt 0">
            <div class="line">
                <span>会员等级</span>
                <span class="fr"><strong style="font-size:16px;color:#FF4907">{pigcms{$level_off_system['lname']}</strong></span>
            </div>
        </if>-->



        <if condition="$score_deducte gt 0">
            <div class="weui-cells weui-cells_form" id="score_money">
                <div class="weui-cell weui-cell_switch">
                    <div class="weui-cell__bd"> 本单可使用{pigcms{$config['score_name']}<span style="color:red"> {pigcms{$score_can_use_count|floatval}个</span> ,可抵扣金额<span style="color:red">{pigcms{$config['Currency_symbol']}{pigcms{$score_deducte|floatval=###}</span></div>
                    <div class="weui-cell__ft">
                        <input class="weui-switch" value="1"  id="use_score" name="use_score"  type="checkbox" <php>if($score_can_use_count==0){</php> readonly="readonly" value="1"<php>}else{</php>  value="0" checked="checked" <php>}</php>>
                    </div>
                </div>
            </div>
        </if>
        <if condition="!empty($first_fee) && $config['village_group_delivery_switch'] eq 1">
            <div class="line" id="show_delivery_fee">
                <span>配送费</span>
                <span class="fr"><strong style="font-size:16px;color:#000">{pigcms{$config['Currency_symbol']}{pigcms{$first_fee}</strong></span>
            </div>
        </if>

        <div class="total-box ">
            <span class="red" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]}; </if> margin-right:5px">待支付：<span class="red" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]}; </if>">{pigcms{$config['Currency_symbol']}</span><span class="red font_size18 font_b actual_total_money" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]}; </if>">{pigcms{$order.actual_total_money_info|floatval}</span></span>
            <span class="" style='    padding-left: 5px;border-left: 1px solid #ddd;color:#999'>已优惠：<span class="">{pigcms{$config['Currency_symbol']}</span><span class=" font_size18 font_b">{pigcms{$order.actual_discount_money|floatval}</span></span>
            <div class="btn"><a class="w100 h100" id="goPay" style="display:inline-block; <if condition='$config["mobile_head_color"]'>background: {pigcms{$config["mobile_head_color"]}; </if>">去支付</a></div>
        </div>
        <input class="hidden" id="is_pick_group" name="is_pick_group" value="<if condition="$config['village_group_delivery_switch'] eq 1">1<else/>0</if>"/>
        <input id="order_count_info" type="hidden" data-actual_total_money="{pigcms{$order.actual_total_money|floatval}" data-delivery_fee="{pigcms{$first_fee}">
        <input class="hidden" id="ouserName" name="ouserName" value="{pigcms{$user_adress['name']}">
        <input class="hidden" id="ouserTel" name="ouserTel" value="{pigcms{$user_adress['phone']}">
        <input class="hidden" id="ouserAddres" name="ouserAddres" value="{pigcms{$user_adress['adress']} {pigcms{$user_adress['detail']}">
        <input class="hidden" id="address_id" name="address_id" value="{pigcms{$user_adress['adress_id']}">
        <input class="hidden" id="oarrivalDate" name="oarrivalDate" value="{pigcms{$dates.0.ymd}">
        <input class="hidden" id="oarrivalTime" name="oarrivalTime" value="{pigcms{$dates.0.date_list.0.hour_minute}">
		{pigcms{$hideScript}
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}villagegroup/js/dialog.js?t=001" charset="utf-8"></script>
		<script type="text/javascript">

            $('#pick_in_store').click(function(){
                $(this).removeClass('pick_in_store').addClass('pick_in_store').siblings('.btn_express').removeClass('pick_in_store');
                $('#li_pick').show();
                $('#li_delivery, #show_arrive_date, #show_arrive_time, #show_delivery_fee, #two_time_select, #deliverExtraPrice, .delivery_fee').css('display', 'none');
                var actual_total_money = $('#order_count_info').data('actual_total_money');

                if(actual_total_money < 0){
                    actual_total_money = 0;
                }
                $('#is_pick_group').val(0);
                $('.actual_total_money').html(actual_total_money);
            });

            $('#post_package').click(function(){
                $(this).removeClass('post_package').addClass('pick_in_store').siblings('.btn_express').removeClass('pick_in_store');
                $('#li_pick').css('display', 'none');
                $('#li_delivery, #show_arrive_date, #show_arrive_time, #show_delivery_fee, #two_time_select, #deliverExtraPrice, .delivery_fee').show();
                $('#is_pick_group').val(1);
                var delivery_fee = $('#order_count_info').data('delivery_fee');

                var actual_total_money = $('#order_count_info').data('actual_total_money');
                console.log('actual_total_money',actual_total_money);

                var payPrice = parseFloat(actual_total_money) + parseFloat(delivery_fee);

                if(payPrice < 0){
                    payPrice = 0;
                }
                $('.actual_total_money').html(payPrice);
            });

            var needDeliverTip = '{pigcms{$dates.0.show_date}' != '今天' ? true : false;
            if(needDeliverTip == true){
                needDeliverTip = {pigcms{$dates.0.date_list.0.timestamp} - 3600 > {pigcms{:time()} ? true :false;
            }

            $('.deliver_time_box .timer .day,.deliver_time_box .timer .time').height($(window).height()*0.7-51);

            if($('#show_arrive_time').css('display') != 'none' && needDeliverTip){
                layer.open({title:'请注意：',content:'最近的配送时间是：'+$('#arriveTime').html(),btn:['确定'],shadeClose:false});
            }

            $('#show_arrive_time').click(function(){
                $('.deliver_time_box .mask').removeClass('hidden');
                $('.deliver_time_box').show();
            });
            $('.deliver_time_box .mask').click(function(){
                $('.deliver_time_box').hide();
            });

            $('.deliver_time_box .day .item').click(function(){
                $(this).addClass('active').siblings().removeClass('active');
                $('.timer-'+$(this).data('date')).show().siblings().hide();
                $('.deliver_time_box .time').scrollTop(0);
            });

            $('.deliver_time_box .time .item').click(function(){
                $('.deliver_time_box .time .item').removeClass('active');
                $(this).addClass('active');
                $('#arriveTime').html($(this).data('show_date')+' '+$(this).data('time'));
                $('#oarrivalDate').val($(this).data('ymd'));
                $('#oarrivalTime').val($(this).data('time'));
                $('#show_delivery_fee strong').html('{pigcms{$config['Currency_symbol']}'+$(this).data('delivery_fee'));

                var delivery_fee = $(this).data('delivery_fee');
                $('#order_count_info').data('delivery_fee', delivery_fee);
                var actual_total_money = $('#order_count_info').data('actual_total_money');

                var payPrice = parseFloat(actual_total_money) + delivery_fee;

                if(payPrice < 0){
                    payPrice = 0;
                }
                $('.actual_total_money').html(payPrice);

                setTimeout(function(){
                    $('.deliver_time_box').hide();
                },150);
            });

			$('#goPay').click(function(){
                console.log("111111")
                var wo_memo = $.trim($("#remarkTxt").html());

                if(wo_memo == '点击添加订单备注') {
                    wo_memo = '';
                }
                $('#omark').val(wo_memo);
                console.log(wo_memo)

                var use_score = 0;
                var coupon_id = "{pigcms{$system_coupon['id']}";
                var coupon_price = "{pigcms{$system_coupon['discount_money']}";
                var use_score = 0;
                if($("#use_score").is(':checked')==true){
                    use_score = 1;
                }
                var is_pick_group = $('#is_pick_group').val();
                var delivery_fee = $('#order_count_info').data('delivery_fee');
                var address_id = $('#address_id').val();
                var oarrivalDate = $('#oarrivalDate').val();
                var oarrivalTime = $('#oarrivalTime').val();
                var ouserTel = $('#ouserTel').val();
                var ouserName = $('#ouserName').val();
                var ouserAddres = $('#ouserAddres').val();
                var msg = {};
                msg['order_id'] = "{pigcms{$order.order_id}";
                msg['use_score'] = use_score;
                msg['coupon_id'] = coupon_id;
                msg['coupon_price'] = coupon_price;
                msg['is_pick_group'] = is_pick_group;
                msg['note'] = wo_memo;
                if (is_pick_group>0) {
                    msg['delivery_fee'] = delivery_fee;
                    msg['address_id'] = address_id;
                    msg['oarrivalDate'] = oarrivalDate;
                    msg['oarrivalTime'] = oarrivalTime;
                    msg['ouserTel'] = ouserTel;
                    msg['ouserName'] = ouserName;
                    msg['ouserAddres'] = ouserAddres;
                }

				var layerLoadTip = layer.open({type: 2,shade: 'background-color: rgba(0,0,0,.6)'});
                $.post("{pigcms{:U('confirm_go_pay')}",msg,function(data){
					layer.close(layerLoadTip);
                    console.log('data',data);
                    if (data.status) {
                        if(typeof wx !=='undefined' && window.__wxjs_environment === 'miniprogram'){
                            wx.miniProgram.navigateTo({url:'/pages/pay/index?order_id={pigcms{$order.plat_order.order_id}&type=plat'});
                            return false;
                        } else {
                            window.location.href = "{pigcms{:U('Pay/check',array('type'=>'plat','order_id'=>$order['plat_order']['order_id']))}";
                        }
                    } else {
                        layer.open({title:'请注意：',content:data.info,btn:['确定'],shadeClose:false,yes:function(index){
                            layer.close(index);
                            if (data.url) {
                                window.location.href=data.url;
                            }
                        }
                        });
                    }
                },'json');
			});

            var  score_deducte = Number("{pigcms{$score_deducte}");
            //点击事件
            $("#use_score").bind("click", function () {
                var  actual_total_money = Number($('.actual_total_money').html());
                if($("#use_score").is(':checked')==true){
                    actual_total_money -= score_deducte;
                    actual_total_money=actual_total_money.toFixed(2)
                }else{
                    actual_total_money += score_deducte;
                    actual_total_money=actual_total_money.toFixed(2)
                }
                $('.actual_total_money').html(actual_total_money);
            });
            $(document).ready(function () {
                // 添加备注
                $('#remarkBtn').bind('click', function(){
                    console.log("====")
                    var remark = $('#remarkTxt').text();remarkBox
                    if(remark == '点击添加订单备注') remark = '';
                    $('#userMark').val(remark);
                    $('#remarkBox').dialog({title: '添加备注'});

                });

                $('#cancleRemark').bind('click', function(){
                    $('#remarkBox').dialog('close');
                });

                $('#saveRemark').bind('click', function(){
                    $('#remarkTxt').text($('#userMark').val());
                    $('#userMark').val('');
                    $('#remarkBox').dialog('close');
                    window.scroll(0, 0);
                });
            })
		</script>
	</body>
</html>