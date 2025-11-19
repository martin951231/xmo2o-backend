<include file="Public:header" />
<script src="{pigcms{$static_public}js/layer/layer.js"></script>
<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
    <php>
        $productLen = count($order['products_arr'])+1;
        $discountLen = count($order['discount_arr'])+1;
    </php>
    <tr>
        <th colspan="1">图维订单号</th>
        <th colspan="6">{pigcms{$order['tw_order_no']}</th>
    </tr>
    <tr>
        <th rowspan="{pigcms{$productLen}">商品信息</th>
        <th>商品名称</th>
        <th>商品编码</th>
        <th>售价</th>
        <th>封面图</th>
        <th>购买数量</th>
        <th>退款数量</th>
    </tr>
    <volist name='order.products_arr' id='p'>
    <tr>
            <th>{pigcms{$p.item_name}</th>
            <th>{pigcms{$p.item_no}</th>
            <th>{pigcms{$p.sale_price}</th>
            <th>
                <if condition="$p['img']">
                    <img src="{pigcms{$p.img}" style="width: 64px;height: 64px;">
                <else/>
                    无
                </if>
            </th>
            <th>{pigcms{$p.sale_qty}</th>
            <th>{pigcms{$p.refund_sale_qty}</th>
    </tr>
    </volist>

    <tr>
        <th colspan="1">下单人手机号</th>
        <th colspan="6">{pigcms{$order['cust_phone']}</th>
    </tr>
    <tr>
        <th colspan="1">下单人</th>
        <th colspan="6">{pigcms{$order['cust_name']}</th>
    </tr>
    <tr>
        <th colspan="1">收货地址</th>
        <th colspan="6">{pigcms{$order['cust_add']}</th>
    </tr>
    <tr>
        <th colspan="1">订单备注</th>
        <th colspan="6">{pigcms{$order['memo']}</th>
    </tr>
    <tr>
        <th colspan="1">状态</th>
        <th colspan="6">
            <if condition="$order['status'] eq 1">
                结账付款成功
            <elseif condition="$order['status'] eq 2"/>
                流水
            <elseif condition="$order['status'] eq 3"/>
                退货
            <else/>
                 --
            </if>
        </th>
    </tr>
    <tr>
        <th colspan="1">下单时间</th>
        <th colspan="6">{pigcms{$order['oper_date']}</th>
    </tr>
    <tr>
        <th colspan="1">订单金额</th>
        <th colspan="6">{pigcms{$order['sheet_amount']}</th>
    </tr>
    <tr>
        <th colspan="1">运费</th>
        <th colspan="6">{pigcms{$order['freight']}</th>
    </tr>

    
    <notempty name='order.discount_arr'>
        <tr>
            <th rowspan="{pigcms{$discountLen}">优惠信息</th>
            <th colspan="3">优惠描述</th>
            <th colspan="3">优惠金额</th>
        </tr>
        <volist name='order.discount_arr' id='d'>
        <tr>
            <th colspan="3">{pigcms{$d.title}</th>
            <th colspan="3">{pigcms{$d.reduce_amount}</th>
        </tr>
        </volist>
    </notempty>

    <tr>
        <th colspan="1">实付金额</th>
        <th colspan="6">{pigcms{$order['real_amount']}</th>
    </tr>
    <tr>
        <th colspan="1">支付方式</th>
        <th colspan="6">{pigcms{$order['pay_type']}</th>
    </tr>
    <tr>
        <th colspan="1">支付时间</th>
        <th colspan="6">{pigcms{$order['pay_time']}</th>
    </tr>
</table>