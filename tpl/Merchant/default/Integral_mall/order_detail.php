<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("积分商城兑换订单")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
	</head>
    <style>
        .frame_form .title {
            width: 18%;
        }
        .frame_form .spec_list th {
            border-top: 1px solid #e5e3e3;
        }
        .frame_form .spec_list th:first-child {
            border-left: 1px solid #e5e3e3;
        }
        .frame_form .spec_list td {
            border-right: 1px solid #e5e3e3;
        }
        .frame_form .spec_list td:first-child {
            border-left: 1px solid #e5e3e3;
        }
    </style>
	<body>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">

        <tr>
            <th class="title">{pigcms{:L_("商品名称")}</th>
            <th colspan="4">{pigcms{$order_info['goods_name']}</th>
        </tr>
        <tr>
            <th class="title">{pigcms{:L_("总计积分")}</th>
            <th colspan="4">{pigcms{$order_info['total_integral']}</th>
        </tr>
        <if condition="$order_info['is_pick_in_store'] eq 1">
        <tr>
            <th class="title">{pigcms{:L_("运费")}</th>
            <th colspan="4">
                {pigcms{$config['Currency_symbol']}{pigcms{$order_info.freight_charge|floatval}
                <span style="font-size: 12px;color: red;"><php>if ($order_info['freight_charges'] == 1 && $order_info['freight_charge'] > 0) {echo '(商户承担)';}</php></span>
            </th>
        </tr>
        </if>
        <tr>
            <th class="title">{pigcms{:L_("商品图片")}</th>
            <th colspan="4"><img src="{pigcms{$order_info.pic_image}" width="150" height="150" class="view_img"></th>
        </tr>
        <tr>
            <th class="title">{pigcms{:L_("订单状态")}</th>
            <th colspan="4">{pigcms{$order_info['show_status']}</th>
        </tr>

         <if condition="$order_info['spec_value'] && $order_info['spec_value']['spec_list'] && $choose_spe">
             <tr>
                 <th class="title">{pigcms{:L_("规格信息")}</th>
                 <th class="spec_list" colspan="4" style="padding: 10px;">
                     <table>
                         <tbody>
                         <tr>
                             <volist name="order_info['spec_value']['spec_list']" id="gs">
                                 <th>{pigcms{$gs['name']}</th>
                             </volist>
                             <th>{pigcms{:L_("积分")}</th>
                             <th>{pigcms{:L_("兑换数量")}</th>
                         </tr>

                         <tr>
                             <volist name="choose_spe['spec']" id="g">
                                 <td>{pigcms{$g['spec_val_name']}</td>
                             </volist>
                             <td>{pigcms{$choose_spe['payment_pure_integral']|floatval}</td>
                             <td>{pigcms{$choose_spe['num']}</td>

                         </tr>
                         </tbody>
                     </table>
                 </th>
             </tr>
             <else/>
             <tr>
                 <th class="title">{pigcms{:L_("积分")}</th>
                 <th colspan="4">{pigcms{$order_info['payment_pure_integral']}</th>
             </tr>
             <tr>
                 <th class="title">{pigcms{:L_("兑换数量")}</th>
                 <th colspan="4">{pigcms{$order_info['num']}</th>
             </tr>
        </if>

    	<tr>
    		<th class="title">{pigcms{:L_("收货人姓名")}</th>
    		<th colspan="4">{pigcms{$order_info['username']}</th>
    	</tr>
    	<tr>
    		<th class="title">{pigcms{:L_("收货人电话")}</th>
    		<th colspan="4">{pigcms{$order_info['userphone']}</th>
    	</tr>
    	
    	<tr>
            <if condition="$order_info['is_pick_in_store'] eq 1">
                <th class="title">{pigcms{:L_("收货人地址")}</th>
                <th colspan="4">{pigcms{$order_info['address']}</th>
                <else/>
                <th class="title">{pigcms{:L_("自提地址")}</th>
                <th colspan="4">{pigcms{$order_info['address']}</th>
            </if>
    	</tr>
    	<tr>
    		<th class="title">{pigcms{:L_("备注")}</th>
    		<th colspan="4"><span style="color:red">{pigcms{$order_info['desc']|default="无"}</span></th>
    	</tr>
        <tr>
            <th class="title">{pigcms{:L_("发货备注")}</th>
            <th colspan="4"><span style="color:red">{pigcms{$order_info['send_note']|default="无"}</span></th>
        </tr>
    	<if condition="$order_info['express_id']">
    	<tr>
    		<th class="title">{pigcms{:L_("快递公司")}</th>
    		<th colspan="4">{pigcms{$order_info['express_name']}</th>
    	</tr>
    	<tr>
    		<th class="title">{pigcms{:L_("快递单号")}</th>
    		<th colspan="4">
                {pigcms{$order_info['express_number']}
                <a href="https://www.kuaidi100.com/chaxun?com={pigcms{$order_info['express_name']}&nu={pigcms{$order_info['express_number']}" target="_blank">{pigcms{:L_("查看物流信息》")}</a>
            </th>
    	</tr>
    	<tr>
    		<th class="title">{pigcms{:L_("发货备注")}</th>
    		<th colspan="4">{pigcms{$order_info['sell_note']|default="无"}</th>
    	</tr>
    	</if>
    	<if condition="$order_info['create_time']">
    	<tr>
    		<th class="title">{pigcms{:L_("下单时间")}</th>
    		<th colspan="4">{pigcms{$order_info['create_time']|date="Y-m-d H:i:s",###}</th>
    	</tr>
    	</if>
    	<if condition="$order_info['pay_time']">
            <tr>
                <th class="title">{pigcms{:L_("支付时间")}</th>
                <th colspan="4">{pigcms{$order_info['pay_time']|date="Y-m-d H:i:s",###}</th>
            </tr>
            <tr>
                <th class="title">{pigcms{:L_("支付方式")}</th>
                <th colspan="4">{pigcms{$order_info['pay_type_str']}</th>
            </tr>
    	</if>
    	<if condition="$order_info['send_time']">
    	<tr>
    		<th class="title">{pigcms{:L_("发货时间")}</th>
    		<th colspan="4">{pigcms{$order_info['send_time']|date="Y-m-d H:i:s",###}</th>
    	</tr>
    	</if>
    	<if condition="$order_info['arrival_time']">
    	<tr>
    		<th class="title">{pigcms{:L_("收货时间")}</th>
    		<th colspan="4">{pigcms{$order_info['arrival_time']|date="Y-m-d H:i:s",###}</th>
    	</tr>
    	</if>
    </table>
</body>
</html>