<include file="Public:header" />
<script src="{pigcms{$static_public}js/layer/layer.js"></script>
<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
    <php>
        $productLen = count($order['goods_arr'])+1;
        $payLen = count($order['pays_arr'])+1;
        $discountLen = count($order['discounts_arr'])+1;
        $clerkLen = count($order['sales_clerk_arr'])+1;
    </php>
    <tr>
        <th colspan="1">零售单号</th>
        <th colspan="8">{pigcms{$order['order_code']}</th>
    </tr>
    <tr>
        <th colspan="1">门店名称</th>
        <th colspan="8">{pigcms{$order['stores_name']}</th>
    </tr>
    <tr>
        <th colspan="1">门店编号</th>
        <th colspan="8">{pigcms{$order['wh_code']}</th>
    </tr>
    <tr>
        <th colspan="1">收银员姓名</th>
        <th colspan="8">{pigcms{$order['cas_name']}</th>
    </tr>
    <tr>
        <th colspan="1">收银员编号</th>
        <th colspan="8">{pigcms{$order['cas_code']}</th>
    </tr>

    <tr><th colspan="9" style="color:red;text-align: center;">商品信息</th></tr>
    <tr>
        <th>名称</th>
        <th>条码</th>
        <th>唯一码</th>
        <th>存货编码</th>
        <th>销售单位</th>
        <th>存货属性</th>
        <th>销售数量</th>
        <th>实销单价</th>
        <th>零售标价</th>
    </tr>
    <volist name='order.goods_arr' id='p'>
        <tr>
            <th>{pigcms{$p.goods_title}</th>
            <th>{pigcms{$p.barcode}</th>
            <th>{pigcms{$p.only_coding}</th>
            <th>{pigcms{$p.goods_spec_code}</th>
            <th>{pigcms{$p.unit}</th>
            <th>{pigcms{$p.spec}</th>
            <th>{pigcms{$p.xnum}</th>
            <th>{pigcms{$p.price}</th>
            <th>{pigcms{$p.retail_price}</th>
        </tr>
    </volist>


    <notempty name='order.discounts_arr'>
        <tr><th colspan="9" style="color:red;text-align: center;">优惠信息</th></tr>
        <tr>
            <th colspan="3">优惠金额</th>
            <th colspan="3">优惠类型</th>
            <th colspan="3">优惠类型名称</th>
        </tr>
        <volist name='order.discounts_arr' id='d'>
            <tr>
                <th colspan="3">{pigcms{$d.discount_money}</th>
                <th colspan="3">{pigcms{$d.discount_type}</th>
                <th colspan="3">{pigcms{$d.discount_type_name}</th>
            </tr>
        </volist>
    </notempty>

    <notempty name='order.sales_clerk_arr'>
        <tr><th colspan="9" style="color:red;text-align: center;">促销员信息</th></tr>
        <tr>
            <th colspan="3">姓名</th>
            <th colspan="3">手机号</th>
            <th colspan="3">编号</th>
        </tr>
        <volist name='order.sales_clerk_arr' id='d'>
            <tr>
                <th colspan="3">{pigcms{$d.sc_name}</th>
                <th colspan="3">{pigcms{$d.sc_phone}</th>
                <th colspan="3">{pigcms{$d.sc_code}</th>
            </tr>
        </volist>
    </notempty>

    <notempty name='order.pays'>
        <tr><th colspan="9" style="color:red;text-align: center;">支付信息</th></tr>
        <tr>
            <th>支付方式名称</th>
            <th>支付金额</th>
            <th>支付时间</th>
            <th>支付单号</th>
            <th>三方流水号</th>
            <th>预售金额</th>
            <th>找零金额</th>
            <th>备注</th>
            <th>外部单号/编码</th>
        </tr>
        <volist name='order.pays_arr' id='d'>
            <tr>
                <th>{pigcms{$d.pay_method_name}</th>
                <th>{pigcms{$d.pay_money}</th>
                <th>{pigcms{$d.pay_time|date='Y-m-d H:i:s',###}</th>
                <th>{pigcms{$d.pay_code}</th>
                <th>{pigcms{$d.pay_serial_no}</th>
                <th>{pigcms{$d.pre_sale_money}</th>
                <th>{pigcms{$d.give_change_money}</th>
                <th>{pigcms{$d.remark}</th>
                <th>{pigcms{$d.out_code}</th>
            </tr>
        </volist>
    </notempty>

    <tr><th colspan="9" style="color:red;text-align: center;">基础信息</th></tr>
    <tr>
        <th colspan="1">订单总金额</th>
        <th colspan="8">{pigcms{$order['order_money']}</th>
    </tr>
    <tr>
        <th colspan="1">订单支付金额</th>
        <th colspan="8">{pigcms{$order['pay_money']}</th>
    </tr>
    <tr>
        <th colspan="1">会员姓名</th>
        <th colspan="8">{pigcms{$order['name']}</th>
    </tr>
    <tr>
        <th colspan="1">会员手机号</th>
        <th colspan="8">{pigcms{$order['mobile']}</th>
    </tr>
    <tr>
        <th colspan="1">会员卡号</th>
        <th colspan="8">{pigcms{$order['card_code']}</th>
    </tr>
    <tr>
        <th colspan="1">订单备注</th>
        <th colspan="8">{pigcms{$order['remark']}</th>
    </tr>
    <tr>
        <th colspan="1">下单时间</th>
        <th colspan="8">{pigcms{$order['addtime']|date='Y-m-d H:i:s',###}</th>
    </tr>
    <tr>
        <th colspan="1">支付时间</th>
        <th colspan="8">{pigcms{$order['pay_time']|date='Y-m-d H:i:s',###}</th>
    </tr>



    

</table>