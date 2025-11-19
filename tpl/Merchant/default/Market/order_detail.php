<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("订单详情")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
	</head>
	<body>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th>{pigcms{:L_("订单编号")}</th>
            <th>{pigcms{:L_("商品信息")}</th>
            <th>{pigcms{:L_("商家信息")}</th>
            <th>{pigcms{:L_("购买信息")}</th>
            <th>{pigcms{:L_("规格商品信息")}</th>
        </tr>
        <volist name="orderList" id="order">
        <tr>
            <td>{pigcms{$order['order_id']}</td>
            <td>{pigcms{:L_("商品名称")}:{pigcms{$order['name']}
            <php> if (!empty($order['number'])) {</php>
            <br/>{pigcms{:L_("商品条形码")}:{pigcms{$order['number']}
            <php>}</php>
            </td>
            <if condition="$_GET['type'] eq 'buy'">
                <td>{pigcms{:L_("卖家商家名称")}:{pigcms{$order['merchant_name']}<br/>{pigcms{:L_("卖家商家电话")}:{pigcms{$order['merchant_phone']}<br/>{pigcms{:L_("卖家店铺名称")}:{pigcms{$order['store_name']}<br/>{pigcms{:L_("卖家店铺电话")}:{pigcms{$order['store_phone']}</td>
            <elseif condition="$_GET['type'] eq 'sell'" />
                <td>{pigcms{:L_("买家商家名称")}:{pigcms{$order['merchant_name']}<br/>{pigcms{:L_("买家商家电话")}:{pigcms{$order['merchant_phone']}</td>
            </if>
            <td>{pigcms{:L_("批发总数")}:{pigcms{$order.num}({pigcms{$order.unit})
                <br/>{pigcms{:L_("批发价")}:{pigcms{$order['price']|floatval}({pigcms{$config['Currency_txt']})
                <br/>{pigcms{:L_("总金额")}:{pigcms{$order.total_price|floatval}({pigcms{$config['Currency_txt']})
                <php> if (!empty($order['discount_info'])) {</php>
                    <br/>{pigcms{:L_("优惠详情：")}<span style="color:red">{pigcms{$order['discount_info']['num']}</span> {pigcms{$order['unit']}, {pigcms{:L_("享受：")}<span style="color:red">{pigcms{$order['discount_info']['discount']}</span> {pigcms{:L_("折优惠")}
                    <label class="col-sm-6" style="display: -webkit-box;">
                        {pigcms{:L_("优惠后的总价")}:<if condition="$order['actual_money']">{pigcms{$order.actual_money|floatval}<else/>{pigcms{$order.money|floatval}</if>({pigcms{$config['Currency_txt']})&nbsp;&nbsp;&nbsp;&nbsp;
                        <if condition="$order['system_discount_info'] gt 0 && $order['system_discount_info'] lt 100">
                        <div style="background-color: #F7511D;color: white;border-radius: 10px;padding: 1px 15px;">
                            {pigcms{:L_("享受")}<span id="discount_totalMoney">{pigcms{$order['system_discount_info']/10|floatval}</span>{pigcms{:L_("折优惠，实际以")}<span id="wholesale_discount">{pigcms{$order['money']|floatval}</span>{pigcms{$config['Currency_txt']}{pigcms{:L_("购买")}
                        </div>
                        </if>
                    </label>
                <php>} else {</php>
                    <if condition="$order['system_discount_info'] gt 0 && $order['system_discount_info'] lt 100">
                        <label class="col-sm-6" style="display: -webkit-box;">
                            {pigcms{:L_("实付总金额")}:{pigcms{$order.money|floatval}({pigcms{$config['Currency_txt']})&nbsp;&nbsp;&nbsp;&nbsp;
                            <div style="background-color: #F7511D;color: white;border-radius: 10px;padding: 1px 15px;">
                                {pigcms{:L_("享受")}<span id="discount_totalMoney">{pigcms{$order['system_discount_info']/10|floatval}</span>{pigcms{:L_("折优惠，实际以")}<span id="wholesale_discount">{pigcms{$order['money']|floatval}</span>{pigcms{$config['Currency_txt']}{pigcms{:L_("购买")}
                            </div>
                        </label>
                    </if>
                <php>}</php>
                {pigcms{:L_("配送费")}:{pigcms{$order.total_express_fee|floatval}({pigcms{$config['Currency_txt']})
            </td>
        
         <if condition="$order['spec_list']">
        <td>
            <table>
            <tbody>
                <tr>
                    <th>{pigcms{:L_("商品条形码")}</th>
                    <volist name="order['spec_list']" id="gs">
                    <th>{pigcms{$gs['name']}</th>
                    </volist>
                    <th>{pigcms{:L_("批发价")}({pigcms{$config['Currency_txt']})</th>
                    <gt name='order.group_id' value='0'>
                        <th>{pigcms{:L_("拼团价")}({pigcms{$config['Currency_txt']})</th>
                    </gt>
                    <th>{pigcms{:L_("本次批发数")}({pigcms{$order['unit']})</th>
                    <th>{pigcms{:L_("总价")}({pigcms{$config['Currency_txt']})</th>
                </tr>
                
                <volist name="order['list']" id="gl">
                    <tr>
                        <td>{pigcms{$gl['number']}</td>
                        <volist name="gl['spec']" id="g">
                        <td>{pigcms{$g['spec_val_name']}</td>
                        </volist>
                        <php>if ($order['is_vip']==1 && $gl['vip_wholesale_price']>0) {</php>
                            <td>{pigcms{$gl['vip_wholesale_price']|floatval}</td>
                        <php>} else {</php>
                            <td>{pigcms{$gl['price']|floatval}</td>
                        <php>}</php>
                        <gt name='order.group_id' value='0'>
                            <td>{pigcms{$gl['group_team_price']|floatval}</td>
                        </gt>
                        <td>{pigcms{$gl['stock_num']}</td>
                        <gt name='order.group_id' value='0'>
                            <td>{pigcms{$gl['stock_num'] * $gl['group_team_price']}</td>
                        <else/>
                            <php>if ($order['is_vip']==1 && $gl['vip_wholesale_price']>0) {</php>
                                <td>{pigcms{$gl['stock_num'] * $gl['vip_wholesale_price']}</td>
                            <php>} else {</php>
                                <td>{pigcms{$gl['stock_num'] * $gl['price']}</td>
                            <php>}</php>
                        </gt>
                        
                    </tr>
                </volist>
            </tbody>
            </table>
        </td>
        <else />
        <td></td>
        </if>
        
        </tr>
        </volist>
        <tr>
            <th>{pigcms{:L_("配送方式")}</th>
            <th colspan="4">

                <if condition="$order['is_pick_in_store'] eq 0">
                    {pigcms{:L_("到店自提")}
                <elseif condition="$order['is_pick_in_store'] eq 1"/>
                    {pigcms{:L_("快递配送")}
                <elseif condition="$order['is_pick_in_store'] eq 2"/>
                    {pigcms{:L_("物流配送")}
                </if>

            </th>
        </tr>

        <if condition="$order['is_pick_in_store'] eq 0">
            <tr>
                <th>{pigcms{:L_("自提点联系电话")}</th>
                <th colspan="4">{pigcms{$userData['userphone']}</th>
            </tr>

            <tr>
                <th>{pigcms{:L_("自提地址")}</th>
                <th colspan="4">{pigcms{$userData['address']}</th>
            </tr>
            <else/>
            <tr>
                <th>{pigcms{:L_("收货人姓名")}</th>
                <th colspan="4">{pigcms{$userData['username']}</th>
            </tr>
            <tr>
                <th>{pigcms{:L_("收货人电话")}</th>
                <th colspan="4">{pigcms{$userData['userphone']}</th>
            </tr>

            <tr>
                <th>{pigcms{:L_("收货人地址")}</th>
                <th colspan="4">{pigcms{$userData['address']}</th>
            </tr>
        </if>
    	<tr>
    		<th>{pigcms{:L_("备注")}</th>
    		<th colspan="4"><span style="color:red">{pigcms{$userData['desc']|default="无"}</span></th>
    	</tr>
    	<if condition="$userData['express_id']">
    	<tr>
    		<th>{pigcms{:L_("快递公司")}</th>
    		<th colspan="4">{pigcms{$userData['express_name']}</th>
    	</tr>
    	<tr>
    		<th>{pigcms{:L_("快递单号")}</th>
    		<th colspan="4">
                {pigcms{$userData['express_number']}
                <if condition="$config['kdniao_appkey']">
					<a class="open_webview" href="{pigcms{:U('Express/detail')}&com={pigcms{:urlencode($userData['express_name'])}&postid={pigcms{$userData['express_number']}">{pigcms{:L_("查看物流信息》")}</a>
				<else/>
					<a href="https://www.kuaidi100.com/chaxun?com={pigcms{$userData['express_name']}&nu={pigcms{:trim($userData['express_number'])}" target="_blank">{pigcms{:L_("查看物流信息》")}</a>
				</if>
            </th>
    	</tr>
    	<tr>
    		<th>{pigcms{:L_("发货/自提备注")}</th>
    		<th colspan="4">{pigcms{$userData['sell_note']|default="无"}</th>
    	</tr>
    	</if>
    	<if condition="$userData['create_time']">
    	<tr>
    		<th>{pigcms{:L_("下单时间")}</th>
    		<th colspan="4">{pigcms{$userData['create_time']|date="Y-m-d H:i:s",###}</th>
    	</tr>
    	</if>
    	<if condition="$userData['pay_time']">
    	<tr>
    		<th>{pigcms{:L_("支付时间")}</th>
    		<th colspan="4">{pigcms{$userData['pay_time']|date="Y-m-d H:i:s",###}</th>
    	</tr>
    	</if>
        <if condition="$userData['payment_third_id']">
            <tr>
                <th>{pigcms{:L_("微信支付流水单号")}</th>
                <th colspan="4">{pigcms{$userData['payment_third_id']}</th>
            </tr>
        </if>
        <if condition="$userData['payment_flow']">
            <tr>
                <th>{pigcms{:L_("支付流水单号")}</th>
                <th colspan="4">{pigcms{$userData['payment_flow']}</th>
            </tr>
        </if>
        <if condition="$userData['pay_type_str']">
            <tr>
                <th>{pigcms{:L_("支付方式")}</th>
                <th colspan="4">{pigcms{$userData['pay_type_str']}</th>
            </tr>
        </if>
    	<if condition="$userData['send_time']">
    	<tr>
    		<th>{pigcms{:L_("发货时间")}</th>
    		<th colspan="4">{pigcms{$userData['send_time']|date="Y-m-d H:i:s",###}</th>
    	</tr>
    	</if>
    	<if condition="$userData['pull_time']">
    	<tr>
    		<th>{pigcms{:L_("收货时间")}</th>
    		<th colspan="4">{pigcms{$userData['pull_time']|date="Y-m-d H:i:s",###}</th>
    	</tr>
    	</if>
    </table>
	<script>
		$('.open_webview').click(function(){
			var url_info =  $(this).attr('href');
			parent.layer.open({
				title:'快递轨迹',
				type:2,
				content:url_info,
				area:['800px','600px'],
				shade: 0.2,
				cancel:function(){

				},
				move:false
			});
			return false;
		});
	</script>
</body>
</html>