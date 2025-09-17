<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>{pigcms{:L_("订单列表")}</title>
    <meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name='apple-touch-fullscreen' content='yes'>
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="format-detection" content="telephone=no">
    <meta name="format-detection" content="address=no">
    <style>
        dl.list dd dl {
            padding-left: 0;
        }

        .dealcard-img {
            margin-left: .2rem
        }

        dl.list dd {
            border: none;
        }

        dl.list dd.dealcard {
            overflow: visible;
            -webkit-transition: -webkit-transform .2s;
            position: relative;
            background: rgba(242, 242, 242, 0.86);
        }

        .dealcard.orders-del {
            -webkit-transform: translateX(1.05rem);
        }

        .dealcard-block-right {
            height: 1.5rem;
        }

        .dealcard .dealcard-brand {
            margin-top: .18rem;
            margin-bottom: .18rem;
            heigth: .5rem
        }

        .dealcard small {
            font-size: .24rem;
            color: #9E9E9E;
        }

        .dealcard weak {
            font-size: .24rem;
            color: #999;
            position: absolute;
            bottom: .15rem;
            left: 0;
            display: block;
            width: 100%;
        }

        .dealcard weak b {
            color: #FDB338;
        }

        .dealcard weak a.btn {
            margin: -.15rem 0;
        }

        .dealcard weak b.dark {
            color: #fa7251;
        }

        .hotel-price {
            color: #ff8c00;
            font-size: .24rem;
            display: block;
        }

        .del-btn {
            display: block;
            width: .45rem;
            height: .45rem;
            text-align: center;
            line-height: .45rem;
            position: absolute;
            left: -.85rem;
            top: 50%;
            background-color: #EC5330;
            color: #fff;
            -webkit-transform: translateY(-50%);
            border-radius: 50%;
            font-size: .4rem;
        }

        .no-order {
            color: #D4D4D4;
            text-align: center;
            margin-top: 1rem;
            margin-bottom: 2.5rem;
        }

        .icon-line {
            font-size: 2rem;
            margin-bottom: .2rem;
        }

        .orderindex li {
            display: inline-block;
            width: 25%;
            text-align: center;
            position: relative;
        }

        .orderindex li.active {
            color: #06c1bb
        }

        .orderindex li .react {
            padding: .28rem 0;
        }

        .orderindex .text-icon {
            display: block;
            font-size: .4rem;
            margin-bottom: .18rem;
        }

        .orderindex .amount-icon {
            position: absolute;
            left: 50%;
            top: .16rem;
            color: white;
            background: #EC5330;
            border-radius: 50%;
            padding: .08rem .06rem;
            min-width: .28rem;
            font-size: .24rem;
            margin-left: .1rem;
            display: none;
        }

        .order-icon {
            display: inline-block;
            width: .5rem;
            height: .5rem;
            text-align: center;
            line-height: .5rem;
            border-radius: .06rem;
            color: white;
            margin-right: .25rem;
            margin-top: -.06rem;
            margin-bottom: -.06rem;
            background-color: #F5716E;
            vertical-align: initial;
            font-size: .3rem;
        }

        .order-all {
            background-color: #2bb2a3;
        }

        .order-zuo, .order-jiudian {
            background-color: #F5716E;
        }

        .order-fav {
            background-color: #0092DE;
        }

        .order-card {
            background-color: #EB2C00;
        }

        .order-lottery {
            background-color: #F5B345;
        }

        .color-gray {
            color: gray;
            border-color: gray;
        }

        .color-gray:active {
            background-color: gray;
        }

        .orderindex li .react.hover {
            color: #FF658E;
        }

        .tabs {
            z-index: 15;
            position: relative;
            background: #FFFFFF;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
            overflow: hidden;
        }

        .tabs-header {
            position: relative;
            overflow: hidden;
        }

        .tabs-header .border {
            position: absolute;
            bottom: 0;
            left: 0;
            background: #06c1bb;
            width: auto;
            height: 2px;
            width: 25%;
            -webkit-transition: 0.3s ease;
            transition: 0.3s ease;
        }

        .tabs-content .tab {
            display: none;
        }

        .tabs-content .tab.active {
            display: block;
        }

        .order-num {
            height: 1rem;
            line-height: 1rem;
            color: #9E9E9E;
            padding-left: .2rem
        }

        .order-num a {
            display: block;
            float: right;
            margin-right: .1rem
        }

        .order-num-foot span:first-child {
            display: block;
            float: left;
            color: #06c1bb;
            line-height: 1rem
        }

        .order-num-foot span:last-child, .order-pay, .order-cancel {
            border: 1px solid #e5e5e5;
            color: #666;
            padding: .2rem;
            border-radius: 4px;
            display: block;
            float: right;
            line-height: .3rem;
            margin: .1rem .1rem 0 0;
        }

        .order-num-foot span.order-pay {
            color: #06c1bb;
            padding: .2rem .5rem
        }

        .pageHide {
            position: fixed;
            width: 100%;
            height: 100%;
            z-index: 100000;
            background: #fff
        }
    </style>
</head>
<body id="index">
<div class="pageHide"></div>
<div id="tips" class="tips"></div>
<dl class="list" style="margin-top:0px;">
    <dd style="display: none">
        <div class="tabs">
            <div class="tabs-header">
                <div class="border" style='background:{pigcms{$config["mobile_head_color"]}'></div>
                <ul class="orderindex">
                    <li class="active" style='color:{pigcms{$config["mobile_head_color"]}'><a href="javascript:void(0)"
                                                                                              tab-id="1" class="react">
                            <span>{pigcms{:L_("已支付")}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </dd>
</dl>
<div style="margin-top:.2rem;">
    <dl class="list tabs-content" id="orders">
        <div class="tab active">
            <empty name="orderlists">
                <p style="text-align:center">{pigcms{:L_("暂无已支付订单")}</p>
            <else/>
                <if condition="($type EQ 'shop') OR ($type EQ 'mall')">
                    <!--外卖、商城-->
                    <link href="{pigcms{$static_path}../../pure/static/css/eve.7c92a906.css" rel="stylesheet"/>
                    <volist name="orderlists" id="order">
                        <dd>
                            <dl style="padding-left:0px">
                                <dd class="order-num">{pigcms{:L_("订单编号")}：<span>{pigcms{$order.real_orderid}</span></dd>
                                <dd class="dealcard dd-padding">
                                    <div class="dealcard-img imgbox">
                                        <img src="{pigcms{$order.logo|replace_file_domain=###}"
                                             style="width:100%;height:100%;"/>
                                    </div>
                                    <div class="dealcard-block-right">
                                        <div class="dealcard-brand single-line">{pigcms{$order.name}</div>
                                        <small>总价：{pigcms{$order['price']|floatval}{pigcms{$config['Currency_txt']}</small>
                                    </div>
                                </dd>
                                <dd class="order-num order-num-foot">
                                    <if condition="$order['status'] eq 0">
                                    <span><php>
                                        if($order['paid'] == 0){
                                            echo L_('待付款');
                                        }else{
                                            echo L_('未确认');
                                        }
                                    </php></span>
                                        <elseif condition="$order['status'] eq 1"/>
                                        <span>{pigcms{:L_("已确认")}</span>
                                        <elseif condition="$order['status'] eq 2"/>
                                        <span>{pigcms{:L_("已消费")}</span>
                                        <elseif condition="$order['status'] eq 3"/>
                                        <span>{pigcms{:L_("已评价")}</span>
                                        <elseif condition="$order['status'] eq 4"/>
                                        <span>{pigcms{:L_("已退款")}</span>
                                        <elseif condition="$order['status'] eq 5"/>
                                        <span>{pigcms{:L_("已取消")}</span>
                                        <elseif condition="$order['status'] eq 7"/>
                                        <span>{pigcms{:L_("分配到自提点")}</span>
                                        <elseif condition="$order['status'] eq 8"/>
                                        <span>{pigcms{:L_("发货到自提点")}</span>
                                        <elseif condition="$order['status'] eq 9"/>
                                        <span>{pigcms{:L_("自提点接货")}</span>
                                        <elseif condition="$order['status'] eq 10"/>
                                        <span>{pigcms{:L_("自提点发货")}</span>
                                    </if>
                                </dd>
                            </dl>
                        </dd>
                    </volist>
                    <elseif condition="$type EQ 'group'"/>
                    <!--团购-->
                    <link href="{pigcms{$static_path}../../pure/static/groupnew/css/base.css" rel="stylesheet"/>
                    <link href="{pigcms{$static_path}../../pure/static/groupnew/css/order_list.css" rel="stylesheet"/>
                    <div class="orderList" id="orderList">
                        <volist name="orderlists" id="item">
                            <div class="item">
                                <div class="title link newWin">
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
                            </div>
                        </volist>
                    </div>
                    <elseif condition="$type EQ 'foodshop'"/>
                    <!--餐饮-->
                    <link href="{pigcms{$static_path}../../pure/static/css/eve.7c92a906.css" rel="stylesheet"/>
                    <volist name="orderlists" id="order">
                        <dd>
                            <dl>
                                <dd class="order-num">{pigcms{:L_("订单编号")}：<span>{pigcms{$order.real_orderid}</span></dd>
                                <dd class="dealcard dd-padding">
                                    <div class="dealcard-img imgbox">
                                        <img src="{pigcms{$order.logo|replace_file_domain=###}"
                                             style="width:100%;height:100%;"/>
                                    </div>
                                    <div class="dealcard-block-right">
                                        <div class="dealcard-brand single-line">{pigcms{$order.name}</div>
                                        <small>总价：{pigcms{$order['price']|floatval}{pigcms{$config['Currency_txt']}</small>
                                    </div>
                                </dd>
                                <dd class="order-num order-num-foot">
                                    <if condition="$order['status'] eq 0">
                                        <span>{pigcms{:L_("待付款")}</span>
                                        <elseif condition="$order['status'] eq 1"/>
                                        <span>{pigcms{:L_("未消费")}</span>
                                        <elseif condition="$order['status'] eq 2"/>
                                        <span>{pigcms{:L_("使用中")}</span>
                                        <elseif condition="$order['status'] eq 3"/>
                                        <span>{pigcms{:L_("已完成")}</span>
                                        <elseif condition="$order['status'] eq 4"/>
                                        <span>{pigcms{:L_("已评价")}</span>
                                        <elseif condition="$order['status'] eq 5"/>
                                        <span>{pigcms{:L_("已退款")}</span>
                                    </if>
                                </dd>
                            </dl>
                        </dd>
                    </volist>
                    <elseif condition="$type EQ 'appoint'"/>
                    <!--预约-->
                    <link href="{pigcms{$static_path}../../pure/static/css/eve.7c92a906.css" rel="stylesheet"/>
                    <volist name="orderlists" id="order">
                        <dd>
                            <dl style="padding-left:0px">
                                <if condition='$order.real_orderid eq 0'>
                                    <dd class="order-num">{pigcms{:L_("订单编号")}：<span>{pigcms{$order.order_id}</span>
                                        <else/>
                                    <dd class="order-num">{pigcms{:L_("订单编号")}：<span>{pigcms{$order.real_orderid}</span>
                                </if>
                                </dd>
                                <dd class="dealcard dd-padding">
                                    <div class="dealcard-img imgbox">
                                        <img src="{pigcms{$order.list_pic}" style="width:100%;height:100%;"/>
                                    </div>
                                    <div class="dealcard-block-right">
                                        <div class="dealcard-brand single-line">{pigcms{$order.appoint_name}</div>
                                        <small>
                                            <if condition='$order["payment_status"] eq 1'>
                                                {pigcms{:L_("定金")}：<if condition='$order["product_id"] gt 0'>{pigcms{$order.product_payment_price}<else />{pigcms{$order.payment_money}</if>&nbsp;&nbsp;
                                            </if>
                                            <if condition='$order["payment_status"] eq 1'>
                                                <if condition='$order["product_id"] gt 0'>{pigcms{:L_("全价")}：{pigcms{$order['product_price']}  {pigcms{$config['Currency_txt']}<else />{pigcms{:L_("全价")}：{pigcms{$order['appoint_price']}  {pigcms{$config['Currency_txt']}</if>
                                                <else />
                                                {pigcms{:L_("全价：面议")}
                                            </if>
                                        </small>

                                    </div>
                                </dd>
                                <dd class="order-num order-num-foot">
                                    <if condition='($order["is_del"] neq 0) || ($order["paid"] eq 3)'>
                                        <span style="color:red">{pigcms{:L_("已取消")}</span>
                                        <elseif condition="$order['service_status'] == '0'" />
                                        <span>{pigcms{:L_("未服务")}</span>
                                        <elseif condition="$order['service_status'] == '1'" />
                                        <span>{pigcms{:L_("已服务")}</span>
                                        <elseif condition="$order['service_status'] == '2'" />
                                        <span>{pigcms{:L_("已评价")}</span>
                                        <elseif condition="empty($order['paid'])" />
                                        <span>{pigcms{:L_("未付款")}</span>
                                    </if>
                                </dd>
                            </dl>
                        </dd>
                        <div style=" height:10px; background:#f0efed"></div>
                    </volist>
                    <elseif condition="$type EQ 'store'"/>
                    <!--到店付-->
                    <link href="{pigcms{$static_path}../../pure/static/css/eve.7c92a906.css" rel="stylesheet"/>
                    <div style="margin-top:.2rem;">
                        <dl class="list" id="orders">
                            <dd>
                                <dl style="padding-left: 0">
                                    <volist name="orderlists" id="order">
                                        <a href="{pigcms{:U('My/store_order_detail',array('order_id'=>$order['order_id']))}">
                                            <dd class="dealcard dd-padding">
                                                <div class="dealcard-img imgbox">
                                                    <img src="{pigcms{$order.image}" style="width:100%;height:100%;"/>
                                                </div>
                                                <div class="dealcard-block-right">
                                                    <div class="dealcard-brand single-line">{pigcms{$order['name']}</div>
                                                    <small>订单号：{pigcms{$order['order_id']}</small>
                                                    <br/>
                                                    <small>总&nbsp;&nbsp;&nbsp;&nbsp;价：{pigcms{$order['total_price']|floatval=###}{pigcms{$config['Currency_txt']}</small>
                                                    <br/>
                                                    <if condition="($order['discount_price']+$order['vip_discount_money']+$order['card_discount_money']) != '0.00' AND $config.open_extra_price eq 0" ><small style="color: red">优惠累积：{pigcms{$order['discount_price']+$order['vip_discount_money']+$order['card_discount_money']|floatval=###}{pigcms{$config['Currency_txt']}</small><br/></if>

                                                    <!-- 	<if condition="$order['card_discount'] != '0.00'" ><small style="color: red">会员卡折扣：{pigcms{$order['card_discount_money']|floatval=###}{pigcms{$config['Currency_txt']}（{pigcms{$order['card_discount']|floatval=###}折）</small><br/>
                                                        </if> -->

                                                    <if condition="$config.open_extra_price eq 1">
                                                        <small style="color: green">实&nbsp;&nbsp;&nbsp;&nbsp;付：{pigcms{$order['price_pay']|floatval=###}{pigcms{$config['Currency_txt']}
                                                            <if condition="$order['score_used_count'] gt 0">+{pigcms{$order.score_used_count}{pigcms{$config.extra_price_alias_name}</if>
                                                        </small>
                                                        <br/>
                                                        <else />
                                                        <small style="color: green">实&nbsp;&nbsp;&nbsp;&nbsp;付：{pigcms{$order['payment_money']+$order['balance_pay']+$order['merchant_balance']+$order['card_give_money']+$order['score_deducte']|floatval=###}{pigcms{$config['Currency_txt']}</small><br/>
                                                    </if>
                                                    <small>买单时间：{pigcms{$order['pay_time']|date="Y-m-d H:i:s", ###}</small>
                                                </div>
                                            </dd>
                                        </a>
                                    </volist>
                                </dl>
                            </dd>
                        </dl>
                    </div>
                    <elseif condition="$type EQ 'gift'"/>
                    <!--V币商城-->
                    <link href="{pigcms{$static_path}../../pure/static/css/eve.7c92a906.css" rel="stylesheet"/>
                    <volist name="orderlists" id="order">
                        <dd>
                            <dl>
                                <dd class="order-num">订单编号：<span>{pigcms{$order.order_id}</span></dd>
                                <dd class="dealcard dd-padding">
                                    <div class="dealcard-img imgbox">
                                        <img src="{pigcms{$order.image_url}" style="width:100%;height:100%;"/>
                                    </div>
                                    <div class="dealcard-block-right">
                                        <div class="dealcard-brand single-line">{pigcms{$order.order_name}</div>
                                        <small>数量：{pigcms{$order.num}&nbsp;&nbsp;
                                            <if condition='$order["exchange_type"] eq 1'>
                                                总价：{pigcms{$order.total_price} {pigcms{$config['Currency_txt']}&nbsp;&nbsp;
                                            </if>
                                            总{pigcms{$config['score_name']}：{pigcms{$order.total_integral}
                                        </small>
                                        <small style="color:red"></small>
                                    </div>
                                </dd>
                                <dd class="order-num order-num-foot">
                                    <if condition="$order['paid'] eq 0">
                                        <font color="red">未支付</font>
                                        <else />
                                        <font color="green">已支付
                                            <if condition="$order['status'] eq 0">
                                                未发货
                                                <elseif condition="$order['status'] eq 1" />
                                                已发货
                                                <elseif condition="$order['status'] eq 3" />
                                                待取货
                                                <else/>
                                                已完成
                                            </if>
                                        </font>
                                    </if>
                                </dd>
                            </dl>
                        </dd>
                        <div style=" height:10px; background:#f0efed"></div>
                    </volist>
                    <elseif condition="$type EQ 'mobile_charge'"/>
                    <!--话费-->
                    <link href="{pigcms{$static_path}../../pure/static/css/eve.7c92a906.css" rel="stylesheet"/>
                    <volist name="orderlists" id="order">
                        <dd>
                            <dl style="padding-left:0px">
                                <dd class="order-num">订单编号：<span>{pigcms{$order.order_id}</span></dd>
                                <dd class="dealcard dd-padding">
                                    <div class="dealcard-block-right" style="    margin-left: 0.5rem;height:0.5rem">
                                        <div class="dealcard-brand single-line">{pigcms{$order.phone} &nbsp;&nbsp;总价：{pigcms{$order['money']|floatval}
                                            {pigcms{$config['Currency_txt']}
                                        </div>


                                    </div>
                                </dd>
                                <dd class="order-num order-num-foot">
                                    <if condition="$order['status'] eq 0">
                                        <span>未扣款</span>
                                        <elseif condition="$order['status'] eq 1"/>
                                        <span>扣款成功</span>
                                        <elseif condition="$order['status'] eq 2"/>
                                        <span>订单提交成功等待服务商充值</span>
                                        <elseif condition="$order['status'] eq 3"/>
                                        <span>充值中</span>
                                        <elseif condition="$order['status'] eq 4"/>
                                        <span>充值成功</span>
                                        <elseif condition="$order['status'] eq 5 OR $order['status'] eq 6"/>
                                        <span>充值失败，已退款</span>
                                        <elseif condition="$order['status'] eq 12"/>
                                        <span>充值失败</span>
                                    </if>
                                </dd>
                            </dl>
                        </dd>
                        <div style=" height:10px; background:#f0efed"></div>
                    </volist>
                    <elseif condition="$type EQ 'sub_card'"/>
                    <!--免单-->
                    <link href="{pigcms{$static_path}../../pure/static/sub_card/css/package_center.css?21511" rel="stylesheet"/>
                    <volist name="orderlists" id="vo">
                        <div class="car_center link-url">
                            <div class="buy_date after">
                                <h4>{pigcms{$vo.name}</h4>
                                <p>
                                    <if condition="$vo.share_uid gt 0">获得时间<else/>购买时间
                                    </if>
                                    : {pigcms{$vo.pay_time|date="Y/m/d H:i:s",###}
                                </p>
                            </div>

                            <p class="frequency after">
                        <span class="ft"><b>{pigcms{$config['Currency_symbol']}{pigcms{$vo.price|floatval}</b><span>/<if
                                        condition="$vo.share_uid gt 0">{pigcms{$vo.store_num}<else/>{pigcms{$vo.free_total_num}</if>次</span></span>
                                <a class="rg" style="border: none" href="javascript:;">共含{pigcms{$vo.store_num}个店铺</a>
                            </p>
                            <div class="car_footer after">
                                <span class="ft">共<if condition="$vo.share_uid gt 0">{pigcms{$vo.store_num}<else/>{pigcms{$vo.free_total_num}</if>次, 已使用{pigcms{$vo.use_count}次</span>
                                <p class="rg"><i></i><span> <if condition="$vo.use_time_type eq 0">永久有效<elseif
                                                    condition="$vo.effective_days gt 0"/>{pigcms{$vo.effective_days}天后到期<elseif
                                                    condition="$vo.effective_days eq 0"/>今天是最后期限<else/>已过期</if></span></p>
                            </div>
                        </div>
                    </volist>
                    <elseif condition="$type EQ 'xinxi'"/>
                    <!--分类信息-->
                    <link href="{pigcms{$static_path}../../pure/static/css/eve.7c92a906.css" rel="stylesheet"/>
                    <volist name="orderlists" id="order">
                        <dd>
                            <dl style="padding-left: 0px">
                                <dd class="order-num">订单编号：<span>{pigcms{$order.order_id}</span>
                                </dd>
                                <dd class="dealcard dd-padding">
                                    <div class="dealcard-block-right" style="margin-left: .5rem">
                                        <div class="dealcard-brand single-line">{pigcms{$order.order_name}</div>
                                        <small>数量：{pigcms{$order.num}&nbsp;&nbsp;总价：{pigcms{$order.total_price}
                                            {pigcms{$config['Currency_txt']}
                                        </small>
                                    </div>
                                </dd>
                                <dd class="order-num order-num-foot">
                                    <if condition="($order['paid'] == 0)">
                                        <span>未付款</span>
                                        <elseif condition="($order['paid'] == 1) && ($order['status'] == 1) && ($order['logistics_status'] == 3)"/>
                                        <span>已收货</span>
                                        <elseif condition="($order['paid'] == 1) && ($order['status'] == 0) && ($order['logistics_status'] == 1) && (!empty($order['express_id']))"/>
                                        <span>已发货</span>
                                        <elseif condition="($order['paid'] == 1) && ($order['status'] == 0) && ($order['logistics_status'] == 3) "/>
                                        <span>已付款</span>
                                        <elseif condition="($order['paid'] == 1) && ($order['status'] == 0) && ($order['logistics_status'] == 2) && (empty($order['express_id']))"/>
                                        <span>未发货</span>
                                        <elseif condition="($order['paid'] == 1) && ($order['status'] == 1) && ($order['logistics_status'] == 4) "/>
                                        <span>已自提</span>
                                        <elseif condition="($order['paid'] == 1) && ($order['status'] == 0) && (empty($order['express_id']) && ($order['is_pick_in_store'] == 1) ) "/>
                                        <span>到店自提</span>
                                    </if>
                                </dd>
                            </dl>
                        </dd>
                        <div style=" height:10px; background:#f0efed"></div>
                    </volist>
                    <elseif condition="$type EQ 'scenic'"/>
                    <link href="{pigcms{$static_path}../../pure/static/scenic/css/css_whir.css" rel="stylesheet"/>
                    <!--景区-->
                    <volist name="orderlists" id="order">
                        <li class="<php>if($order['order_status'] == 1){echo 'on';}else if($order['order_status'] == 3){echo 'off';}</php>"
                            style="background: #fff;margin: 2% 3% 0 3%;border-left: #bdbdbd 3px solid;padding: 0 2%;">
                            <a>
                                <div class="Admissticket_top clr">
                                    <div class="Adticket_left">
                                        <h5>{pigcms{$order.scenic_list.scenic_title}</h5>
                                        <p>游玩日期：{pigcms{$order.ticket_time}</p>
                                        <p>下单日期：{pigcms{$order.add_time}</p>
                                    </div>

                                    <if condition="in_array($order['order_status'],[3,6,1])">
                                        <div class="Adticket_right">
                                            <php>
                                                if($order['order_status'] == 3){
                                                echo '已完成';
                                                }else if($order['order_status'] == 6){
                                                echo '已过期';
                                                }else{
                                                echo '未开始';
                                                }
                                            </php>
                                        </div>
                                    </if>
                                </div>
                            </a>

                            <div class="Admissticket_end clr">
                                <php>
                                    if($order['park_count'] != 0){
                                    echo '<span>含停车</span>';
                                    }else if($order['guide_count'] != 0){
                                    echo '<span>含导游</span>';
                                    }else if($order['mate_count'] != 0){
                                    echo '<span>需游伴</span>';
                                    }

                                    if($order['order_status'] == 0){
                                    echo '<a>未支付</a>';
                                    }else if($order['order_status'] == 1){
                                    echo '<a class="on">已支付</a>';
                                    }else if($order['order_status'] == 2){
                                    echo '<a>未评论</a>';
                                    }else if($order['order_status'] == 3){
                                    echo '<a>已评论</a>';
                                    }else if($order['order_status'] == 4){
                                    echo '<a>已退款</a>';
                                    }else if($order['order_status'] == 5){
                                    echo '<a>已过期</a>';
                                    }else if($order['order_status'] == 6){
                                    echo '<a>已过期</a>';
                                    }
                                </php>
                            </div>
                        </li>
                    </volist>
                    <else/>
                    <dd>
                    <dd class="dealcard dd-padding" style=" text-align:center; background:#fff; width:100%">{pigcms{:L_("暂无订单")}
                    </dd>
                    </dd>
                </if>
            </empty>
</div>
</dl>
</div>
<script src="{pigcms{:C('JQUERY_FILE')}"></script>
<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
<script type="text/javascript" language="javascript">
    window.onload = function () {
        var _this = this;
        var main_color = '{pigcms{$config["mobile_head_color"]}';
        var is_index = true;
        $('.pageHide').bind("touchmove", function (e) {
            e.preventDefault();
        });
        show();
        $(".pageHide").hide()

        function scroollShow() {
            is_index = true;
            $("html,body").animate({"scrollTop": $shop_top * 1}, 10, function () {
                $(".pageHide").hide()
            });

        }

        function show() {
            var activePos = $('.tabs-header .active').position();
            var url = "{pigcms{:U('ajax_shop_order_list')}"

            function changePos() {
                activePos = $('.tabs-header .active').position();
                $('.border').stop().css({
                    left: activePos.left,
                    width: $('.tabs-header .active').width()
                });
            }

            changePos();
            var tabHeight = $('.tab.active').height();

            function animateTabHeight() {
                tabHeight = $('.tab.active').height();
                $('.tabs-content').stop().css({height: tabHeight + 'px'});
            }

            var tabItems = $('.tabs-header ul li');
            var tabCurrentItem = tabItems.filter('.active');
            $('.tabs-header a').on('click', function (e) {
                e.preventDefault();
                return false
                var tabId = $(this).attr('tab-id');
                sessionStorage.setItem("shop_order_list_index", parseInt(tabId - 1));
                $('.tabs-header a').stop().parent().removeClass('active');
                $('.tabs-header ul li').css('color', '');
                $(this).stop().parent().addClass('active').css('color', main_color);
                changePos();
                tabCurrentItem = tabItems.filter('.active');
                $('.tab').stop().fadeOut(300, function () {
                    $(this).removeClass('active');
                }).hide();

                $('.tab[tab-id="' + tabId + '"]').stop().fadeIn(300, function () {
                    var status = $('.tabs-header ul li.active').data('status');
                    $.get(url, {
                        'status': status, 'store_id': {pigcms{$_GET.store_id | intval}}, function(data) {
                            if (data.status) {
                                var shtml = '<dd>';
                                var order_list = data['order_list'];
                                for (var i in order_list) {
                                    shtml += '<dl><dd class="order-num">{pigcms{:L_("订单编号")}：<span>' + order_list[i]["real_orderid"] + '</span>';

                                    shtml += '</dd>';
                                    shtml += '<dd class="dealcard dd-padding goUrl" data-url="' + order_list[i]['order_url'] + '">';
                                    shtml += '<div class="dealcard-img imgbox">';
                                    shtml += '<img src="' + order_list[i]['image'] + '" style="width:100%;height:100%;"/>';
                                    shtml += '</div>';
                                    shtml += '<div class="dealcard-block-right">';
                                    shtml += '<div class="dealcard-brand single-line">' + order_list[i]['name'] + '</div>';
                                    shtml += '<small>{pigcms{:L_("数量")}：' + order_list[i]['num'] + '&nbsp;&nbsp;{pigcms{:L_("总价")}：' + order_list[i]['price'] + ' {pigcms{$config['
                                    Currency_txt
                                    ']}</small>';

                                    shtml += '</div></dd><dd class="order-num order-num-foot">	';

// 								var url = "{pigcms{:U('My/shop_order_refund')}";
// 								url +='&order_id='+order_list[i]['order_id']+'&mer_id='+order_list[i]['mer_id']+'&store_id='+order_list[i]['store_id'];


                                    if (order_list[i]['status'] == 0) {
                                        shtml += '<span style="color:' + main_color + '">{pigcms{:L_("未确认")}</span>';
                                    } else if (order_list[i]['status'] == 1) {
                                        shtml += '<span>{pigcms{:L_("已确认")}</span>';
                                    } else if (order_list[i]['status'] == 2) {
                                        var url = "{pigcms{:U('My/shop_feedback')}";
                                        url += '&order_id=' + order_list[i]['order_id'];
                                        shtml += '<span style="color:' + main_color + '" onclick="location.href=\'' + url + '\'">{pigcms{:L_("待评价")}</span>';
                                    } else if (order_list[i]['status'] == 3) {
                                        if (order_list[i]['goods_type_num'] > 1) {
                                            var url = "{pigcms{:U('My/shop_feedback')}";
                                            url += '&order_id=' + order_list[i]['order_id'];
                                            shtml += '<span style="color:' + main_color + '" onclick="location.href=\'' + url + '\'">{pigcms{:L_("已评价")}</span><span style="display: none;"></span>';
                                        } else {
                                            shtml += '<span style="color:' + main_color + '">{pigcms{:L_("已评价")}</span><span style="display: none;"></span>';
                                        }
                                    } else if (order_list[i]['status'] == 4) {
                                        shtml += '<span style="color:' + main_color + '">{pigcms{:L_("已退款")}</span>';
                                    } else if (order_list[i]['status'] == 5) {
                                        shtml += '<span style="color:' + main_color + '">{pigcms{:L_("已取消")}</span>';
                                    } else if (order_list[i]['status'] == 6) {
                                    } else if (order_list[i]['status'] == 7) {
                                        shtml += '<span style="color:' + main_color + '">{pigcms{:L_("分配到自提点")}</span>';
                                    } else if (order_list[i]['status'] == 8) {
                                        shtml += '<span style="color:' + main_color + '">{pigcms{:L_("发货到自提点")}</span>';
                                    } else if (order_list[i]['status'] == 9) {
                                        shtml += '<span style="color:' + main_color + '">{pigcms{:L_("自提点接货")}</span>';
                                    } else if (order_list[i]['status'] == 10) {
                                        shtml += '<span style="color:' + main_color + '">{pigcms{:L_("自提点发货")}</span>';
                                    }

                                    shtml += '</dd></dl><div style=" height:10px; background:#f0efed"></div>';
                                }
                            } else {
                                var shtml = '<dd><dd class="dealcard dd-padding" style=" text-align:center; background:#fff; width:100%">{pigcms{:L_("暂无订单")}</dd></dd>';
                            }
                            $('.tab[tab-id="' + tabId + '"]').html(shtml);
                            if (!is_index) {
                                scroollShow()
                            }
                        }, 'json')
                });
            });


        }

    }


</script>
<!--include file="Public:footer"/-->
{pigcms{$hideScript}
<style>
    .goUrl, .order-pay {
        cursor: pointer;
    }
</style>

</body>
</html>
