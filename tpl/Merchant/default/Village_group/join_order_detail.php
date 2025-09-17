<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
    <title>{pigcms{:L_("社区团购订单详情")}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
</head>

<style>
    .delivery-progress{
        padding: 12px 15px;
        background: #fff;
        margin-top: 15px;
        color: #999;
        font-size: 12px;
    }
    .delivery-progress:hover th {
        background-color: #eef3f6;
    }

    .delivery-progress .item{
        margin-top: 16px;
    }

    .red, .delivery-progress .red, .delivery-progress .item.is-delivery .delivery-time{
        color: #ff4c29;
    }

    .delivery-progress .icon-right{
        font-size: 12px;
        vertical-align: middle;
        margin-left: 5px;
    }

    .delivery-progress .txt{
        width: 34px;
        height: 34px;
        line-height: 34px;
        border-radius: 50%;
        color: #fff;
        text-align:center;
        display: inline-block;
        background: #ff4c29;
        margin-right: 10px;
        font-size: 14px;
        font-weight: bold;
        box-sizing: border-box;
    }

    .delivery-progress .progress-line{
        position: absolute;
        left: 15px;
        background: #ff4c29;
        width: 4px;
        height: 10px;
    }

    .delivery-progress .static .txt{
        background: #fff;
        border: 3px solid #ccc;
        color: #ccc;
        line-height: 28px;
    }

    .delivery-progress .static .progress-line{
        background: #ccc;
    }

    .delivery-progress .bottom{
        bottom: -10px;
    }

    .delivery-progress .top{
        top: -10px;
    }

    .delivery-progress .delay-btn{
        margin-left: 20px;
        background: #24B9AA;
        color: #fff;
        padding: 4px 8px;
        border-radius: 3px;
    }

    .delivery-progress .desc{
        width: calc(100% - 44px);
        width:-webkit-calc(100% - 44px);
    }

    .delivery-progress .item.is-delayed{
        color: #ccc;
    }

    .delivery-progress .item.is-delayed .txt{
        background: #fff;
        border: 3px dashed #ccc;
        color: #ccc;
        line-height: 28px;
    }

    .delivery-progress .item.is-delayed .delay-btn{
        background: #ccc;
        padding: 4px 14px;
    }

    .delivery-progress .item.is-delayed .progress-line{
        background: #ccc;
    }

    .delivery-progress .item.is-delivery .delay-btn, .delivery-progress .item.static .check-detail, .delivery-progress .item.is-delayed .check-detail,
    .delivery-progress .progress-detail .item:first-child .top, .delivery-progress .progress-detail .item:last-child .bottom{
        display: none;
    }

    .delivery-progress .progress-detail .item:first-child{
        margin-top: 0;
    }
    .fl {
        float: left;
    }

</style>
<body>
<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">

	
	<tr>
		<th colspan="1">{pigcms{:L_("订单号")}</th>
		<th colspan="2">{pigcms{$order['real_orderid']}</th>
	</tr>

	<if condition="$order.orderid neq 0">
	<tr>
		<th colspan="1">{pigcms{:L_("流水号")}</th>
		<th colspan="3"><if condition="$order['pay_type'] neq 'baidu'"><if condition="$order['meal_type']" >takeout_<else />food_</if> </if>{pigcms{$order['orderid']}</th>
	</tr>
	</if>
	<if condition="$order_detail">
	<tr>
		<th width="180">{pigcms{:L_("商品名称")}</th>
		<th>{pigcms{:L_("单价")}</th>
        <th>{pigcms{:L_("数量")}</th>
        <th>{pigcms{:L_("会员等级优惠")}</th>

	</tr>
	<volist name="order_detail" id="vo">
	<tr>
		<th width="180" style="color:#9F0050">{pigcms{$vo['name']}</th>
        <if condition="$vo['area_price'] gt 0">
            <th style="color:#9F0050">{pigcms{$vo['area_price']+$vo['increase_price']|floatval}</th>
            <else/>
            <th style="color:#9F0050">{pigcms{$vo['price']+$vo['increase_price']|floatval}</th>
        </if>
        <th style="color:#9F0050"><strong>{pigcms{$vo['num']|floatval}</strong> </th>
        <th>
            <if condition="$order['good_discount'][$vo['goods_id']]['type'] eq 1">
                <strong style="font-size:14px;color:#FF4907">{pigcms{$order['good_discount'][$vo['goods_id']]['offstr']}</strong>
                <elseif condition="$order['good_discount'][$vo['goods_id']]['type'] eq 3" />
                <strong style="font-size:14px;color:#FF4907">{pigcms{$order['good_discount'][$vo['goods_id']]['offstr']}</strong>
            </if>
        </th>

	</tr>
	</volist>
	</if>

    <if condition="$order['village_group_type'] eq 1">
        <tr>
            <th colspan="4">{pigcms{:L_("团购类型：周期购")}</th>
        </tr>
        <tr>
            <th colspan="4">{pigcms{:L_("期数：")}{pigcms{$order['delivery_periods']}{pigcms{:L_("（当前期数：")}{pigcms{$order['newest_delivery_periods']}）</th>
        </tr>
        <else/>
        <tr>
            <th colspan="4">{pigcms{:L_("团购类型：普通")}</th>
        </tr>
    </if>
    <tr>
        <th colspan="4">{pigcms{:L_("客户姓名：")}{pigcms{$user['nickname']}</th>
    </tr>
    <tr>
        <th colspan="4">{pigcms{:L_("客户姓名：")}{pigcms{$user['nickname']}</th>
    </tr>
	<tr>
		<th colspan="4">{pigcms{:L_("客户手机：")}{pigcms{$user['phone']}</th>
	</tr>


	<tr>
		<th colspan="4">{pigcms{:L_("下单时间：")}{pigcms{$order['add_time']|date="Y-m-d H:i:s",###} </th>
	</tr>

	<tr>
		<th colspan="4">{pigcms{:L_("订单总金额：")}{pigcms{$order['total_money']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
    <if condition="$order.vip_discount_money gt 0">
        <tr>
            <th colspan="4">{pigcms{:L_("等级优惠金额：")}{pigcms{$order['vip_discount_money']|floatval} {pigcms{$config['Currency_txt']}</th>
        </tr>
    </if>
    <if condition="$order.discount_price gt 0">
        <tr>
            <th colspan="4">{pigcms{:L_("优惠后金额：")}{pigcms{$order['discount_price']|floatval} {pigcms{$config['Currency_txt']}</th>
        </tr>
    </if>
    <tr>
        <th colspan="4">{pigcms{:L_("实付金额：")}{pigcms{$order['actual_discount_money']|floatval} {pigcms{$config['Currency_txt']}</th>
    </tr>



	<if condition="$order['score_used_count']">
	<tr>
		<th colspan="4">{pigcms{:L_("使用")}{pigcms{$config.score_name}：{pigcms{$order['score_used_count']} </th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{$config.score_name}{pigcms{:L_("抵现：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['score_deducte']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	
	<if condition="$order['merchant_balance']">
	<tr>
		<th colspan="4">{pigcms{:L_("商家余额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['merchant_balance']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
     <!-- 平台E卡 -->
    <if condition="$order['pay_info']['use_ecard_price']">
    <tr>
        <th colspan="4">{pigcms{$config.ecard_alias_name}支付：{pigcms{$config['Currency_symbol']}{pigcms{$order['pay_info']['use_ecard_price']|floatval} {pigcms{$config['Currency_txt']}</th>
    </tr>
    </if>
	<if condition="$order['balance_pay']">
	<tr>
		<th colspan="4">{pigcms{:L_("平台余额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['balance_pay']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	<if condition="$order['payment_money']">
	<tr>
		<th colspan="4">{pigcms{:L_("在线支付：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['payment_money']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
    <if condition="$order['delivery_fee']">
        <tr>
            <th colspan="4">配送费：{pigcms{$config['Currency_symbol']}{pigcms{$order['delivery_fee']|floatval} {pigcms{$config['Currency_txt']}</th>
        </tr>
    </if>


	<if condition="$order['pay_type']">
	<tr>
		<th colspan="4">{pigcms{:L_("订单支付方式：")}{pigcms{$order['pay_type']}</th>
	</tr>
	</if>
	
	<tr>
		<th colspan="4" style="color:red">{pigcms{:L_("订单状态：")}<if condition="$order.status eq 2 ">
												{pigcms{:L_("已完成")}
											<elseif condition="$order['status'] egt 1 AND $order['status'] lt 10 OR $order['status'] eq 13" />
												{pigcms{:L_("已支付")}
											<elseif condition="$order['status'] eq 11 " />
												{pigcms{:L_("已取消")}


											<elseif condition="$order['status'] eq 10 " />
												{pigcms{:L_("超时未支付")}
											<elseif condition="$order['status'] eq 0 " />
												{pigcms{:L_("未支付")}
											</if>
		</th>
	</tr>
	
	<tr>
		<th colspan="4" style="color:red">{pigcms{:L_("配送状态：")}<if condition="$order['status'] eq '1'">
												{pigcms{:L_("待发货")}
											<elseif condition="$order.status eq 7" />
												{pigcms{:L_("部分已发货")}
											<elseif condition="$order.status eq 5" />
												{pigcms{:L_("全部已发货")}
											<elseif condition="$order.status eq 8" />
												{pigcms{:L_("部分已到货")}
											<elseif condition="$order.status eq 6" />
												{pigcms{:L_("全部已到货")}
											<elseif condition="$order.status eq 3" />
												{pigcms{:L_("部分已自提")}
											<elseif condition="$order.status eq 2" />
												{pigcms{:L_("全部已自提")}
                <elseif condition="$order.status eq 13" />
                {pigcms{:L_("已评价")}
											<elseif condition="$order.status eq 11" />
												-
											</if>
		</th>
	</tr>
	
	<tr>
		<th colspan="4">{pigcms{:L_("备注")}:{pigcms{$order['comment']|default="无"}</th>
	</tr>




    <if condition="$order['village_group_type'] eq 1 && $order['status'] gt 0 && $order['status'] neq 10">
        <tr>
            <th colspan="4" class="red">{pigcms{:L_("周期购配送相关信息")}</th>
        </tr>
        <tr>
            <th colspan="2">
                <span class="red">{pigcms{:L_("配送日期")}</span>
            </th>
            <th colspan="2">
                <div><span class="red">{pigcms{$cycle_order_count}</span> / {pigcms{$order.delivery_periods}</div>
            </th>
        </tr>

        <volist name="cycle_order_detail" id="item">
            <tr>
                <th colspan="2" class="delivery-progress">
                    <div class="item <if condition="$item['is_delay'] eq 1">is-delayed<elseif condition="$item['status'] gt 0 && $item['status'] neq 6"/>is-delivery<else/>static</if>">
                        <div class="rel fl">
                            <span class="progress-line top"></span>
                            <span class="txt">
                                <if condition="$item['is_delay'] eq 1">-<else/>{pigcms{$item['date_num']}</if>
                            </span>
                            <span class="progress-line bottom"></span>
                        </div>

                        <div class="delivery-time fl">
                            <div>
                                <if condition="$item['status'] gt 0 && $item['status'] neq 6">
                                    {pigcms{:L_("已配送")}
                                    <if condition="$item['real_send_time'] && $item['real_send_time'] lt $item['send_time']">
                                        {pigcms{:L_("（提前配送）")}
                                    </if>
                                    <elseif condition="$item['is_delay'] neq 1 && $item['status'] eq 0"/>
                                    {pigcms{:L_("待配送")}
                                    <elseif condition="$item['is_delay'] eq 1 || $item['status'] eq 6"/>
                                    {pigcms{:L_("已延期")}
                                </if>
                            </div>
                            <div>{pigcms{$item['send_time']|date='Y-m-d',###}</div>
                        </div>
                    </div>
                </th>

                <th colspan="2" class="delivery-progress">
                    <div class="item <if condition="$item['is_delay'] eq 1">is-delayed<elseif condition="$item['status'] gt 0 && $item['status'] neq 6"/>is-delivery<else/>static</if>">

                    <if condition="$item['real_send_time'] && $item['is_delay'] neq 1 && $item['status'] gt 0 && $item['status'] neq 6">
                        <div>{pigcms{$item['real_send_time']|date='Y-m-d H:i:s',###}</div>
                        <else/>
                        <div class="delay-btn fl">
                            <if condition="$item['is_delay'] neq 1">
                                <span class="delay_submit" data-detail_id="{pigcms{$item['id']}">{pigcms{:L_("正常待配送")}</span>
                                <elseif condition="$item['is_delay'] eq 1 && $item['delay_user'] eq 0"/>
                                {pigcms{:L_("已延期")}
                                <elseif condition="$item['is_delay'] eq 1 && $item['delay_user'] eq 1"/>
                                {pigcms{:L_("团长已延期")}
                                <elseif condition="$item['is_delay'] eq 1 && $item['delay_user'] eq 2"/>
                                {pigcms{:L_("管理员已延期")}
                                <elseif condition="$item['is_delay'] eq 1 && $item['delay_user'] eq 3"/>
                                {pigcms{:L_("商家已延期")}
                            </if>
                        </div>
                    </if>

                </th>
            </tr>
        </volist>
    </if>
</table>
</body>
</html>