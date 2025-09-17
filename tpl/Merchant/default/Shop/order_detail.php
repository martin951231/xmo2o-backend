<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
        <script src="{pigcms{$static_public}js/layer/layer.js"></script>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("店铺管理中心")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
        <style>
            .merchant-refund-reason-input {
                margin: 0 10px;
                flex: 1;
                height: 30px;
                padding: 0 10px;
                box-sizing:border-box;
            }
            .merchant-refund-reason-btn {
                display:flex;
                justify-content:space-between;
                align-items:center;
                white-space: nowrap;
            }
            .merchant-refund-reason-btn .btn {
                height:28px;
                line-height:26px;
                text-align:center;
                padding: 0 10px;
                margin: 0 10px;
                border-radius: 3px;
                box-sizing:border-box;
                cursor: pointer;
                color: #fff;
            }
            .merchant-refund-reason-btn .btn.btn-agree {
                background-color: green;
            }
            .merchant-refund-reason-btn .btn.btn-refuse {
                background-color: red;
            }
        </style>
	</head>
	<body>
<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
	<tr>
		<th colspan="1">{pigcms{:L_("订单编号")}</th>
		<th colspan="13">{pigcms{$order['real_orderid']}</th>
	</tr>
	<if condition="$order['tw_order_no']">
	<tr>
		<th colspan="1">{pigcms{:L_("图维订单编号")}</th>
		<th colspan="13">{pigcms{$order['tw_order_no']}</th>
	</tr>
	</if>

	<if condition="$order.orderid neq 0">
	<tr>
		<th colspan="1">{pigcms{:L_("流水号")}</th>
		<th colspan="6"><if condition="$order['pay_type'] neq 'baidu'">shop_</if>{pigcms{$order['orderid']}</th>
	</tr>
	</if>
	<if condition="$order['shop_pass']">
	<tr>
		<th colspan="1">{pigcms{:L_("消费码")}</th>
		<th colspan="6">{pigcms{$order['shop_pass']}</th>
	</tr>
	</if>
	<tr>
		<th width="180"><strong>{pigcms{:L_("商品名称")}</strong></th>
		<th><strong>商品条形码</strong></th>
		<th><strong>{pigcms{:L_("原价")}</strong></th>
		<th><strong>{pigcms{:L_("优惠类型")}</strong></th>
		<th><strong>{pigcms{:L_("优惠率")}</strong></th>
		<th><strong>{pigcms{:L_("优惠价")}</strong></th>
		<th><strong>{pigcms{:L_("数量")}</strong></th>
    <th><strong>称码重量</strong></th>
		<th><strong>{pigcms{:L_("规格属性详情")}</strong></th>

        <th><strong>{pigcms{:L_("附属商品")}</strong></th>
        <th><strong>{pigcms{:L_("价格")}</strong></th>
        <th><strong>{pigcms{:L_("数量")}</strong></th>
        <th><strong>{pigcms{:L_("规格属性详情")}</strong></th>
	</tr>
	<volist name="order['info']" id="rowset">
	<if condition="!empty($rowset['name'])">
	<tr><td colspan="14">{pigcms{$rowset['name']}</td></tr>
	</if>
	<volist name="rowset['list']" id="vo">
        <tr>
            <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}">{pigcms{$vo['name']}</th>
			<th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}">{pigcms{$vo['number']}</th>
            <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}">{pigcms{$vo['price']|floatval}<if condition="$config.open_extra_price eq 1 AND $vo.extra_price gt 0">+{pigcms{$vo.extra_price}{pigcms{$config.extra_price_alias_name}</if></th>
            <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}"><php>if ($vo['discount_type'] == 1 || $vo['discount_type'] == 4) {echo '{pigcms{:L_("店铺折扣")}';} elseif ($vo['discount_type'] == 2 || $vo['discount_type'] == 5) {echo '{pigcms{:L_("分类折扣")}';} elseif ($vo['discount_type'] == 3) {echo 'VIP{pigcms{:L_("折扣")}';} if($vo['is_give'] == 1){echo '满赠';}</php></th>
            <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}"><strong><php>if ($vo['discount_rate']) echo $vo['discount_rate']/10;</php></strong></th>
            <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}"><strong><php>if ($vo['discount_price']) { echo floatval($vo['discount_price']); } else { echo floatval($vo['price']); }</php></strong></th>
            <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}"><strong>{pigcms{$vo['num']}</strong><php>if ($vo['unit']) {</php> ({pigcms{$vo['unit']})<php>}</php></th>
          <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}">
            <php>if ($vo['code_weight']) { echo floatval($vo['code_weight']).'g'; }</php>
          </th>
            <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}" <?php if($vo['info_count'] == 0) echo 'colspan="8"'; ?> > {pigcms{$vo['spec']} </th>
        </tr>
        <volist name="vo['info']" id="info">
            <tr>
                <th style="color:#9F0050">{pigcms{$info['name']}</th>
                <th>{pigcms{$info['price']}</th>
                <th><strong>{pigcms{$info['num']}</strong> ({pigcms{$info['unit']})</th>
                <th>{pigcms{$info['spec']}</th>
            </tr>
        </volist>
	</volist>
	</volist>
	<tr>
		<th colspan="14">{pigcms{:L_("备注")}:<span style="color:red">{pigcms{$order['desc']|default="无"}</span></th>
	</tr>
	<if condition="$order['pick_username'] neq ''">
	<tr>
		<th colspan="15">{pigcms{:L_("自提客户姓名")}：{pigcms{$order['pick_username']}</th>
	</tr>       
   </if>
	<if condition="$order['username']">
        <if condition="$order['is_pick_in_store'] != 2">
            <if condition="$order['username']">
                <tr>
                    <th colspan="15">{pigcms{:L_("客户姓名")}：{pigcms{$order['username']}</th>
                </tr>
            </if>
        </if>
        <if condition="$order['is_pick_in_store'] == 2">
            <if condition="$order['userphone']">
                <tr>
                    <th colspan="15">{pigcms{:L_("用户自提联系方式")}：
	                    <if condition="$order['virtual_phone_arr']">
	                    	{pigcms{$order['userphone']|phone_show}  
							<?php if($order['virtual_phone_overtime'] >= time()){ ?>
								/{pigcms{:L_("隐私号码")}
								{pigcms{$order['virtual_phone_arr'][0][0]}
								<?php if($order['virtual_phone_arr'][0][1]){ ?>
									{pigcms{:L_('转')}{pigcms{$order['virtual_phone_arr'][0][1]}
								<?php } ?>  
							<?php } ?>
	                    <else/>
	                    	{pigcms{$order['userphone']|phone_demo_show}                    
	                	</if>
                	</th>
                </tr>
            </if>
            <else/>
            <if condition="$order['userphone']">
                <tr>
                    <th colspan="15">{pigcms{:L_("客户手机")}：
	                    <if condition="$order['virtual_phone_arr']">
	                    	{pigcms{$order['userphone']|phone_show}  
							<?php if($order['virtual_phone_overtime'] >= time()){ ?>
								 / {pigcms{:L_("隐私号码")}
								{pigcms{$order['virtual_phone_arr'][0][0]}
								<?php if($order['virtual_phone_arr'][0][1]){ ?>
									{pigcms{:L_('转')}{pigcms{$order['virtual_phone_arr'][0][1]}
								<?php } ?> 
							<?php } ?>
	                    <else/>
	                    	{pigcms{$order['userphone']|phone_demo_show}                    
	                	</if>
                	</th>
                </tr>
            </if>
        </if>
    </if>
	<if condition="$order['register_phone']">
	<tr>
		<th colspan="14" style="color:red">{pigcms{:L_("客户注册手机：")} 
	        <if condition="$order['virtual_phone_arr']">
            	{pigcms{$order['register_phone']|phone_show}  
				<?php if($order['virtual_phone_overtime'] >= time()){ ?>
					 / {pigcms{:L_("隐私号码")}
					<if condition="count($order['virtual_phone_arr'])==1">
					{pigcms{$order['virtual_phone_arr'][0][0]}
					<?php if($order['virtual_phone_arr'][0][1]){ ?>
						{pigcms{:L_('转')}{pigcms{$order['virtual_phone_arr'][0][1]}
					<?php } ?>  
					<else/>
					{pigcms{$order['virtual_phone_arr'][1][0]}
					<?php if($order['virtual_phone_arr'][1][1]){ ?>
						{pigcms{:L_('转')}{pigcms{$order['virtual_phone_arr'][1][1]}
					<?php } ?>  
					</if>    
				<?php } ?>
            <else/>
            	{pigcms{$order['register_phone']|phone_demo_show}                    
        	</if>
		</th>
	</tr>
	</if>
    <php>if ($order['order_from'] != 6) {</php>
	<if condition="$order['is_pick_in_store'] eq 2">
	<tr>
		<th colspan="14">{pigcms{:L_("自提地址：")}{pigcms{$order['address']}</th>
	</tr>
	<else />
	<tr>
		<th colspan="14">{pigcms{:L_("客户地址：")}{pigcms{$order['user_adress']['province_txt']} &nbsp;{pigcms{$order['user_adress']['city_txt']}  &nbsp;{pigcms{$order['user_adress']['area_txt']}  &nbsp;{pigcms{$order['address']}</th>
	</tr>
	</if>
    <if condition="$order['platform'] eq 0">
	<tr>
		<th colspan="14">{pigcms{:L_("配送方式：")}{pigcms{$order['deliver_str']}</th>
	</tr>
	<tr>
		<th colspan="14">{pigcms{:L_("配送状态：")}{pigcms{$order['deliver_status_str']}</th>
	</tr>
    </if>

	<if condition="$order['is_pick_in_store'] eq 0 AND $order['no_bill_money'] gt 0">
	<tr>
		<th colspan="14">{pigcms{:L_("支付平台费用：")}{pigcms{$order['no_bill_money']|floatval}</th>
	</tr>
	</if>
	<if condition="$order['is_pick_in_store'] eq 3 AND $order['express_id']">
	<tr>
		<th colspan="14">{pigcms{:L_("快递公司：")}{pigcms{$order['express_name']}</th>
	</tr>
	<tr>
		<th colspan="14">{pigcms{:L_("快递单号：")}{pigcms{$order['express_number']}</th>
	</tr>
	</if>
	<if condition="$order['deliver_user_info']">
         <if condition ="$order['dingding_trade_no'] !=''">
			 <tr>
				 <th colspan="14">{pigcms{:L_("配送状态：")}{pigcms{$orderRecord['order_status']}</th>
			 </tr>
			 <tr>
				 <th colspan="14">{pigcms{:L_("配送员姓名：")}{pigcms{$orderRecord['psy_name']}</th>
			 </tr>
			 <tr>
				 <th colspan="14">{pigcms{:L_("配送员电话：")}{pigcms{$orderRecord['psy_mobile']}</th>
			 </tr>
		 <else/>
			 <tr>
				 <th colspan="14">{pigcms{:L_("配送员姓名：")}{pigcms{$order['deliver_user_info']['name']}</th>
			 </tr>
			 <tr>
				 <th colspan="14">{pigcms{:L_("配送员电话：")}{pigcms{$order['deliver_user_info']['phone']}</th>
			 </tr>
		</if>

	</if>
    <php>} else {</php>
    <tr>
        <th colspan="14">{pigcms{:L_("订单来源：线下零售")} </th>
    </tr>
    <php>}</php>

	<tr>
		<th colspan="14">{pigcms{:L_("下单时间：")}{pigcms{$order['create_time']|date="Y-m-d H:i:s",###} </th>
	</tr>
	<if condition="$order['pay_time']">
        <tr>
            <th colspan="14">{pigcms{:L_("支付时间：")}{pigcms{$order['pay_time']|date="Y-m-d H:i:s",###} </th>
        </tr>
	</if>
    <if condition="$order['cashier_order_time']">
        <tr>
            <th colspan="14">{pigcms{:L_("店员接单时间：")}{pigcms{$order['cashier_order_time']|date="Y-m-d H:i:s",###} </th>
        </tr>
    </if>
	<if condition="$order['expect_use_time']">
	<tr>
        <th colspan="14">
            <php>if($order['is_pick_in_store'] == 2){</php>
            {pigcms{:L_("自提取货时间：")}
            <if condition="$order['is_right_now']">
				<span style="color: red;">{pigcms{:L_('立即送达')} </span>{pigcms{$order['expect_use_time']|date="H:i",###}
			<else/>
				{pigcms{$order['expect_use_time']|date="Y-m-d H:i",###}
			</if>
            - {pigcms{$order['expect_use_time']+1200|date="H:i",###}
            <php>}else{</php>
            {pigcms{:L_("期望送达时间：")}

			<if condition="$order['is_right_now']">
				<span style="color: red;">{pigcms{:L_('立即送达')} </span>{pigcms{$order['expect_use_time']|date="H:i",###}
			<else/>
				{pigcms{$order['expect_use_time']|date="Y-m-d H:i",###}
			</if>
             - {pigcms{$order['expect_use_time']+900|date="H:i",###}
            <php>}</php>
        </th>
	</tr>
	</if>
  <if condition="$order['use_time']">
	<tr>
		<th colspan="14">{pigcms{:L_("送达时间：")}{pigcms{$order['use_time']|date="Y-m-d H:i:s",###}</th>
	</tr>
	</if>
    <if condition="$order['goods_price'] gt 0">
	<tr>
		<th colspan="14">{pigcms{:L_("商品总价：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['discount_price']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
    </if>
	<if condition="$order['packing_charge'] gt 0">
	<tr>
		<th colspan="14">{pigcms{$store['pack_alias']|default=L_("打包费：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['packing_charge']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	<if condition="$order['freight_charge'] gt 0">
	<tr>
		<th colspan="14">{pigcms{$store['freight_alias']|default=L_("配送费用：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['freight_charge']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
    <if condition="$order['other_money'] gt 0">
    <tr>
        <th colspan="14">{pigcms{:L_("加价送的金额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['other_money']|floatval} {pigcms{$config['Currency_txt']}</th>
    </tr>
    </if>
	<tr>
		<th colspan="14">{pigcms{:L_("订单总价：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['discount_price'] + $order['packing_charge'] + $order['freight_charge'] + $order['other_money']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>

    <php>if (!empty($order['discount_detail'])) {</php>
    <volist name="order['discount_detail']" id="detail">
    <tr>
        <th colspan="14">{pigcms{:L_("优惠信息：")}
        <php>if ($detail['discount_type'] == 1) {</php>{pigcms{:L_("平台首单满")}<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b> <php>if ($detail['plat_money'] > 0 || $detail['merchant_money'] > 0) {echo '('.L_("平台补贴").':<b style="color: red">' . floatval($detail['plat_money']) . '</b>'.L_("商家补贴").':<b style="color: red">' . floatval($detail['merchant_money']) .'</b>)';}</php>
        <php>} elseif ($detail['discount_type'] == 2) {</php>{pigcms{:L_("平台满")}<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b> <php>if ($detail['plat_money'] > 0 || $detail['merchant_money'] > 0) {echo '('.L_("平台补贴").':<b style="color: red">' . floatval($detail['plat_money']) . '</b>'.L_("商家补贴").':<b style="color: red">' . floatval($detail['merchant_money']) . '</b>)';}</php>
        <php>} elseif ($detail['discount_type'] == 3) {</php>{pigcms{:L_("商家首单满")}<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b>
        <php>} elseif ($detail['discount_type'] == 4) {</php>{pigcms{:L_("商家满")}<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b>
        <php>} elseif ($detail['discount_type'] == 30) {</php>{pigcms{:L_("平台配送订单满")}<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b> <php>if ($detail['plat_money'] > 0 || $detail['merchant_money'] > 0) {echo '('.L_("平台补贴").':<b style="color: red">' . floatval($detail['plat_money']) . '</b>'.L_("商家补贴").':<b style="color: red">' . floatval($detail['merchant_money']) . '</b>)';}</php>
        <php>} elseif ($detail['discount_type'] == 5) {</php>{pigcms{:L_("平台配送订单满")}<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b> <php>if ($detail['plat_money'] > 0 || $detail['merchant_money'] > 0) {echo '('.L_("平台补贴").':<b style="color: red">' . floatval($detail['plat_money']) . '</b>'.L_("商家补贴").':<b style="color: red">' . floatval($detail['merchant_money']) . '</b>)';}</php>
        <php>} elseif ($detail['discount_type'] == 6) {</php>{pigcms{:L_("商家配送订单满")}<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b>
        <php>}</php>
        </th>
    </tr>
    </volist>
    <php>} else {</php>
	<if condition="$order['merchant_reduce'] gt 0">
	<tr>
		<th colspan="14">{pigcms{:L_("店铺优惠：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['merchant_reduce']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	<if condition="$order['balance_reduce'] gt 0">
	<tr>
		<th colspan="14">{pigcms{:L_("平台优惠：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['balance_reduce']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
    <php>}</php>
	<if condition="$order['card_discount'] neq 0 AND $order['card_discount'] neq 10">
	<tr>
		<th colspan="14">{pigcms{:L_("会员卡：")}{pigcms{$order['card_discount']|floatval} {pigcms{:L_("折优惠")}</th>
	</tr>
	</if>
	<tr>
		<th colspan="14">{pigcms{:L_("应付总价：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['price']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	<if condition="$order['score_used_count'] gt 0">
	<tr>
		<th colspan="14">{pigcms{:L_("使用")}{pigcms{$config.score_name}：{pigcms{$order['score_used_count']} </th>
	</tr>
	<tr>
		<th colspan="14">{pigcms{$config.score_name}{pigcms{:L_("抵现：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['score_deducte']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>

	<if condition="$order['card_give_money'] gt 0">
	<tr>
		<th colspan="14">{pigcms{:L_("会员卡余额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['card_give_money']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	<gt name="order.use_ecard_price" value='0'>
		<tr>
			<th colspan="14">
				{pigcms{$config.ecard_alias_name}支付：{pigcms{$config.Currency_symbol}{pigcms{$order.use_ecard_price|floatval}{pigcms{$config.Currency_txt}
			</th>
		</tr>
	</gt>
	<if condition="$order['merchant_balance'] gt 0">
	<tr>
		<th colspan="14">{pigcms{:L_("商家余额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['merchant_balance']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	<if condition="$order['balance_pay'] gt 0">
	<tr>
		<th colspan="14">{pigcms{:L_("平台余额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['balance_pay']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
    <if condition="$order['internal_card_pay_money'] gt 0">
        <tr>
            <th colspan="14">{pigcms{:L_("一卡通余额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['internal_card_pay_money']|floatval} {pigcms{$config['Currency_txt']}</th>
        </tr>
    </if>
	<if condition="$order['payment_money'] gt 0">
	<tr>
		<th colspan="14">
            {pigcms{:L_("在线支付：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['payment_money']|floatval} {pigcms{$config['Currency_txt']}
            <if condition="$order['is_own'] eq 1">
                <span style="color: red;">（自有支付）</span>
            </if>
        </th>
	</tr>
	</if>
	<notempty name="order['service_charge']">
	<tr>
		<th colspan="14">{pigcms{:L_("在线支付手续费")}：{pigcms{$config['Currency_symbol']}{pigcms{$order['service_charge']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</notempty>
	<if condition="$order['card_id']">
	<tr>
		<th colspan="14">{pigcms{:L_("店铺优惠券金额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['card_price']} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	<if condition="$order['coupon_id']">
	<tr>
		<th colspan="14">{pigcms{:L_("平台优惠券金额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['coupon_price']} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	<if condition="$order['pay_type'] eq 'offline' AND empty($order['third_id'])">
	<tr>
		<th colspan="14">{pigcms{:L_("线下需支付：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['price']-$order['card_price']-$order['merchant_balance']-$order['balance_pay']-$order['payment_money']-$order['score_deducte']-$order['coupon_price']|floatval}{pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	<tr>
		<th colspan="14">{pigcms{:L_("支付状态：")}{pigcms{$order['pay_status']}</th>
	</tr>
    <if condition="$order['platform'] eq 0">
	<tr>
		<th colspan="14">{pigcms{:L_("支付方式：")}{pigcms{$order['pay_type_str']}</th>
	</tr>
    </if>
	<tr>
		<th colspan="14">{pigcms{:L_("订单状态：")}{pigcms{$order['status_str']}
			<if condition="($order['status'] eq 4) AND ($order['refund_time'] gt 0)">&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{:L_("退款时间")}:{pigcms{$order['refund_time']|date="Y-m-d H:i:s",###}</if>
			<if condition="($order['status'] eq 5) AND ($order['cancel_time'] gt 0)">&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{:L_("取消时间")}:{pigcms{$order['cancel_time']|date="Y-m-d H:i:s",###}</if>
		</th>
	</tr>
    <?php if(!empty($refund_list)){ ?>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%" style="margin-top:38px;">
        <tr>
            <th colspan="8"><strong>{pigcms{:L_("退货信息")}</strong></th>
        </tr>
        <volist name="refund_list" id="rowset">
            <tr>
                <th colspan="8"><strong>{pigcms{:L_("退货商品")}</strong></th>
            </tr>
            <tr>
                <th><strong>{pigcms{:L_("商品名称")}</strong></th>
                <th><strong>{pigcms{:L_("单价")}</strong></th>
                <th><strong>{pigcms{:L_("数量")}</strong></th>
                <th><strong>{pigcms{:L_("规格属性详情")}</strong></th>

                <th><strong>{pigcms{:L_("附属商品")}</strong></th>
                <th><strong>{pigcms{:L_("价格")}</strong></th>
                <th><strong>{pigcms{:L_("数量")}</strong></th>
                <th><strong>{pigcms{:L_("规格属性详情")}</strong></th>
            </tr>
            <volist name="rowset['goodsList']" id="vo">
                <tr>
                    <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}">{pigcms{$vo['name']}</th>
                    <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}">{pigcms{$vo['price']|floatval}</th>
                    <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}"><strong>{pigcms{$vo['num']}</strong><php>if ($vo['unit']) {</php> ({pigcms{$vo['unit']})<php>}</php></th>
                    <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}" <?php if($vo['info_count'] == 0) echo 'colspan="5"'; ?>>{pigcms{$vo['spec']}</th>
                </tr>
                <volist name="vo['info']" id="info">
                    <tr>
                        <th style="color:#9F0050">{pigcms{$info['name']}</th>
                        <th>{pigcms{$info['price']}</th>
                        <th><strong>{pigcms{$info['num']}</strong> ({pigcms{$info['unit']})</th>
                        <th>{pigcms{$info['spec']}</th>
                    </tr>
                </volist>
            </volist>
            <tr>
                <th colspan="8">{pigcms{:L_("退款备注")}: <strong style="color:red">{pigcms{$rowset['reason']}</strong></th>
            </tr>
            <tr>
                <th colspan="8">{pigcms{:L_("退款金额")}: <strong style="color:red">{pigcms{$config['Currency_symbol']}{pigcms{$rowset['price']} {pigcms{:L_("不含配送费、打包费、优惠金额")}</strong></th>
            </tr>
            <tr>
                <th colspan="8">{pigcms{:L_("退款凭证")}:
                    <ul class="imgs" style="display:inline">
                        <php>foreach ($rowset['image'] as $image) {</php>
                        <li style="display:inline">
                            <img src="{pigcms{:file_domain()}{pigcms{$image}" height="50" class="refundImg"/>
                        </li>
                        <php>}</php>
                    </ul>
                </th>
            </tr>
            <tr>
                <th colspan="8">{pigcms{:L_("退款状态")}:
                    {pigcms{$rowset.showStatus}
                    <if condition="$rowset.reply_content neq ''">
                        &nbsp;&nbsp;&nbsp;&nbsp;{pigcms{:L_("理由：")}<span style="color: red;font-weight: bold;">{pigcms{$rowset.reply_content}</span>
                    </if>
                    <if condition="in_array($rowset['status'],array(0,3,4))">
                        &nbsp;&nbsp;&nbsp;&nbsp;{pigcms{$rowset.applytime|date="Y-m-d H:i",###}
                        <elseif condition="in_array($rowset['status'],array(1,2))"/>
                        &nbsp;&nbsp;&nbsp;&nbsp;{pigcms{$rowset.reply_time|date="Y-m-d H:i",###}
                    </if>
                </th>
            </tr>
            <?php if($rowset['status']==0){ ?>
            <tr>
                <th colspan="8">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                    {pigcms{:L_("退款操作")}:
                        <input type="text" id="refund_reason_merchant" class="merchant-refund-reason-input" placeholder="请填写原因" autocomplete="off" />
                        <div class="merchant-refund-reason-btn">
                            <div class="btn btn-agree" data-refund_id="{pigcms{$rowset['id']}" data-type="agree">{pigcms{:L_("同意")}</div>
                            <div class="btn btn-refuse" data-refund_id="{pigcms{$rowset['id']}" data-type="disagree">{pigcms{:L_("不同意")}</div>
                        </div>
                    </div>
                </th>
            </tr>
            <?php } ?>
        </volist>
    </table>
    <?php } ?>
	<if condition="$order['invoice_head']">
	<tr>
		<th colspan="14">{pigcms{:L_("发票抬头")}:{pigcms{$order['invoice_head']}</th>
	</tr>
	</if>
	<if condition="$order['cue_field']">
		<tr>
			<th colspan="14">&nbsp;</th>
		</tr>
		<tr>
			<th colspan="14"><strong>{pigcms{:L_("分类填写字段")}</strong></th>
		</tr>
		<volist name="order['cue_field']" id="vo">
			<tr>
				<th colspan="1">{pigcms{$vo.title}</th>
				<th colspan="6">{pigcms{$vo.txt}</th>
			</tr>
		</volist>
	</if>
</table>
<script type="text/javascript">
			$(function(){
				$('#merchant_remark_btn').click(function(){
					$(this).html('{pigcms{:L_("提交中")}...').prop('disabled',true);
					$.post("{pigcms{:U('Group/group_remark',array('order_id'=>$now_order['order_id']))}",{merchant_remark:$('#merchant_remark').val()},function(result){
						$('#merchant_remark_btn').html('{pigcms{:L_("修改")}').prop('disabled',false);
						alert(result.info);
					});
				});
				$('#store_id_btn').click(function(){
					$(this).html('{pigcms{:L_("提交中")}...').prop('disabled',true);
					$.post("{pigcms{:U('Group/order_store_id',array('order_id'=>$now_order['order_id']))}",{store_id:$('#order_store_id').val()},function(result){
						$('#store_id_btn').html('{pigcms{:L_("修改")}').prop('disabled',false);
						alert(result.info);
					});
				});
                $('.refundImg').click(function(){
                    var obj = $(this);
                    layer.open({
                        type: 1,
                        title: false,
                        closeBtn: 0,
                        skin: 'layui-layer-nobg', //"没有背景色")}
                        shadeClose: true,
                        content: '<img src="' + obj.attr('src') + '" style="height:100%;width:100%"/>'
                    });
                });
                // 弹框关闭
                let closeDialog = function() {
                    if(window.top && window.top.closeiframe) {
                        window.top.closeiframe()
                        window.top.main_refresh && window.top.main_refresh()
                    } else if(parent){
                        parent.layer.close(parent.layer.getFrameIndex(window.name));
                        parent.location.reload()
                    }
                };
                var can_click = true;
                // 同意 || 不同意退款
                $('.merchant-refund-reason-btn .btn').click(function() {
                    let type = $(this).attr('data-type')
                    let reason = $('.merchant-refund-reason-input').val()
                    let refund_id = $(this).attr('data-refund_id')
                    let order_id = '{pigcms{$order["order_id"]}'
                    if(!reason.trim()) {
                        alert('请填写原因');
                        return
                    }
                    can_click = false;
                    let refundRequest = function() {
                        // 请求接口
                        var reply_content = $(this).parent('th').find('input[name="reply_content"]').val();

                        $.post("{pigcms{:U('Store/replyRefund')}", {'order_id':order_id, 'refund_id':refund_id, 'reply_content':reason, 'type':type, 'is_mer':1}, function(response){
                            can_click = true;
                            if (response.errcode == 1) {
                                layer.msg(response.msg);
                            } else {
                                closeDialog()
                                location.reload();
                            }
                        }, 'json');
                    }
                    let msg = type == 'agree'?"确认同意退款":"确认不同意退款"
                    if(window.top && window.top.art) {
                        window.top.art.dialog({
                            padding: 0,
                            title: '提示',
                            lock: true,
                            content: msg,
                            okType: 'primary',
                            ok: function () {
                                refundRequest() 
                            }
                        });
                    } else {
                        layer.confirm(msg,{offset: '50px'}, function (index) {
                            refundRequest()
                        });
                    }
                });
			});
		</script>
	</body>
</html>