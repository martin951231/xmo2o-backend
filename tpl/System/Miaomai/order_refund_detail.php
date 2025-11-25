<include file="Public:header" />
<script src="{pigcms{$static_public}js/layer/layer.js"></script>

<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
    <if condition="$refundTimes gt 1">
        <tr>
            <th colspan="1">退货批次</th>
            <th colspan="8">
                <select id="refund_batch">
                    <volist name='refundIds' id='r'>
                        <option value="{pigcms{$r}" <if condition="$_GET['refund_id'] eq $r">selected</if>>第{pigcms{$key+1}次退货</option>
                    </volist>
                </select>
            </th>
        </tr>
    </if>

    <php>
        $productLen = count($order['goods_arr'])+1;
        $payLen = count($order['pays_arr'])+1;
    </php>
    <tr>
        <th colspan="1">零售退货单号</th>
        <th colspan="8">{pigcms{$order['ro_code']}</th>
    </tr>
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
        <th colspan="9" style="color:red;text-align: center;">退货单商品信息</th>
    </tr>
    <tr>
        <th>名称</th>
        <th>条码</th>
        <th>唯一码</th>
        <th>存货编码</th>
        <th>销售单位</th>
        <th>存货属性</th>
        <th>销售数量</th>
        <th>退货单价</th>
        <th>退货小计</th>
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
            <th>{pigcms{$p.refund_price}</th>
        </tr>
    </volist>

    <notempty name='order.pays'>
        <tr>
            <th colspan="9" style="color:red;text-align: center;">退货单支付信息</th>
        </tr>
        <tr>
            <th>支付方式名称</th>
            <th>支付金额</th>
            <th>支付时间</th>
            <th>支付单号</th>
            <th>三方流水号</th>
            <th>原路退款状态</th>
            <th>备注</th>
            <th colspan="2">原支付单号</th>
        </tr>
        <volist name='order.pays_arr' id='d'>
            <tr>
                <th>{pigcms{$d.pay_method_name}</th>
                <th>{pigcms{$d.pay_money}</th>
                <th>{pigcms{$d.pay_time|date='Y-m-d H:i:s',###}</th>
                <th>{pigcms{$d.pay_code}</th>
                <th>{pigcms{$d.pay_serial_no}</th>
                <th>{pigcms{$d.road_pay_status}</th>
                <th>{pigcms{$d.remark}</th>
                <th colspan="2">{pigcms{$d.former_code}</th>
            </tr>
        </volist>
    </notempty>

    <tr><th colspan="9" style="color:red;text-align: center;">基础信息</th></tr>
    <tr>
        <th colspan="1">原零售单支付金额</th>
        <th colspan="8">{pigcms{$order['pay_money']}</th>
    </tr>
    <tr>
        <th colspan="1">退款金额</th>
        <th colspan="8">{pigcms{$order['refund_money']}</th>
    </tr>
    <tr>
        <th colspan="1">退货类型</th>
        <th colspan="8">{pigcms{$order['type']}</th>
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
</table>

<script type="text/javascript">
    $('#refund_batch').change(function() {
        var refund_id = $(this).val();
        window.location.href="{pigcms{:U('Miaomai/order_refund_detail',array('order_code'=>$order['order_code']))}"+"&refund_id="+refund_id;
    })
</script>