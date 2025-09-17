<include file="Public:header"/>
<style>
    .delivery-progress{
        padding: 12px 15px;
        background: #fff;
        margin-top: 15px;
        color: #999;
        font-size: 12px;
    }

    .delivery-progress .item{
        margin-top: 16px;
    }

    .delivery-progress .red, .delivery-progress .item.is-delivery .delivery-time{
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
        background: #ff4c29;
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

</style>
<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">

    
    <tr>
        <th colspan="1">订单号</th>
        <th colspan="4">{pigcms{$order['real_orderid']}</th>
    </tr>

    <if condition="$order.pay_info.orderid neq '0'">
    <tr>
        <th colspan="1">流水号</th>
        <th colspan="4">plat_{pigcms{$order['pay_info']['orderid']}</th>
    </tr>
    </if>
    <if condition="$order_detail">
    <tr>
        <th width="180">商品名称</th>
        <th>单价</th>
        <th>数量</th>
        <th>打包费</th>
        <th>会员等级优惠</th>

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
        <th style="color:#9F0050"><strong>{pigcms{$vo['packing_fee']|floatval}</strong> </th>
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


    <if condition="$order['village_group_type'] eq 1 && $order['status'] gt 0 && $order['status'] neq 10">
        <tr>
            <th colspan="2">团购类型：周期购</th>
        </tr>
        <tr>
            <th colspan="3">期数：{pigcms{$order['delivery_periods']}（当前期数：{pigcms{$order['newest_delivery_periods']}）</th>
        </tr>
        <else/>
        <tr>
            <th colspan="5">团购类型：普通</th>
        </tr>
    </if>



<!--退款信息开始-->
    <if condition="$finishRefunds">

            <tr>
                <th colspan="8"><strong>{pigcms{:L_("已退商品")}</strong></th>
            </tr>
            <tr>
                <th><strong>{pigcms{:L_("商品名称")}</strong></th>
                <th><strong>{pigcms{:L_("退款单价")}</strong></th>
                <th><strong>{pigcms{:L_("退款数量")}</strong></th>
            </tr>
            <volist name="finishRefunds" id="finish">
                <tr>
                    <th style="color:#9F0050" rowspan="{pigcms{$finish['info_count']+1}">{pigcms{$finish['name']}</th>
                    <th style="color:#9F0050" rowspan="{pigcms{$finish['info_count']+1}">{pigcms{$finish['price']|floatval}</th>
                    <th style="color:#9F0050" rowspan="{pigcms{$finish['info_count']+1}"><strong>{pigcms{$finish['num']}</strong><php>if ($finish['unit']) {</php> ({pigcms{$finish['unit']})<php>}</php></th>
                    <th style="color:#9F0050" rowspan="{pigcms{$finish['info_count']+1}" <?php if($finish['info_count'] == 0) echo 'colspan="5"'; ?>>{pigcms{$finish['spec']}</th>
                </tr>
                <volist name="finish['info']" id="info">
                    <tr>
                        <th style="color:#9F0050">{pigcms{$info['name']}</th>
                        <th>{pigcms{$info['price']}</th>
                        <th><strong>{pigcms{$info['num']}</strong> ({pigcms{$info['unit']})</th>

                    </tr>
                </volist>
            </volist>
    </if>
    <volist name="refund_list" id="rowset">
            <tr>
                <th colspan="4"><strong>{pigcms{:L_("退货信息")}</strong></th>
            </tr>
            <tr>
                <th colspan="4"><strong>{pigcms{:L_("退货商品")}</strong></th>
            </tr>
            <tr>
                <th><strong>{pigcms{:L_("商品名称")}</strong></th>
                <th><strong>{pigcms{:L_("单价")}</strong></th>
                <th><strong>{pigcms{:L_("数量")}</strong></th>
            </tr>
            <volist name="rowset['goodsList']" id="vo">
                <tr>
                    <th style="color:#9F0050">{pigcms{$vo['name']}</th>
                    <th style="color:#9F0050">{pigcms{$vo['price']|floatval}</th>
                    <th style="color:#9F0050"><strong>{pigcms{$vo['num']}</strong><php>if ($vo['unit']) {</php> ({pigcms{$vo['unit']})<php>}</php></th>

                </tr>
            </volist>
            <tr>
                <th colspan="4">{pigcms{:L_("退款备注")}: <strong style="color:red">{pigcms{$rowset['reason']}</strong></th>
            </tr>
            <tr>
                <if condition="$rowset['balance_money'] gt 0">
                    <th colspan="4">{pigcms{:L_("退款金额")}: <strong style="color:red">{pigcms{$config['Currency_symbol']}{pigcms{$rowset['balance_money']} </strong></th>
                    <else/>
                    <th colspan="4">{pigcms{:L_("退款金额")}: <strong style="color:red">{pigcms{$config['Currency_symbol']}{pigcms{$rowset['pay_money']} </strong></th>
                </if>

            </tr>
            <tr>
                <th colspan="4">{pigcms{:L_("退款凭证")}:
                    <ul class="imgs" style="display:inline">
                        <php>foreach ($rowset['image'] as $image) {</php>
                        <if condition="$image">
                            <li style="display:inline">
                                <img src="{pigcms{:file_domain()}{pigcms{$image}" height="50" class="refundImg"/>
                            </li>
                        </if>
                        <php>}</php>
                    </ul>
                </th>
            </tr>

            <tr>
                <th colspan="4">{pigcms{:L_("退款状态")}:

                    <if condition="$refund_list['showStatus'] eq 0"><span>{pigcms{:L_("正在审核中")} <php>if (!empty($finishRefunds['reply_content'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$finishRefunds['reply_content']}</strong><php>}</php></span>
                        <elseif condition="$refund_list['showStatus'] eq 1"/><span>{pigcms{:L_("商家审核通过")} <php>if (!empty($finishRefunds['reply_content'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$finishRefunds['reply_content']}</strong><php>}</php></span>
                        <elseif condition="$refund_list['showStatus'] eq 2"/><span>{pigcms{:L_("商家拒绝退款")} <php>if (!empty($finishRefunds['reply_content'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$finishRefunds['reply_content']}</strong><php>}</php></span>
                        <elseif condition="$refund_list['showStatus'] eq 3"/><span>{pigcms{:L_("您重新申请退款")} <php>if (!empty($finishRefunds['reply_content'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$finishRefunds['reply_content']}</strong><php>}</php></span>
                        <elseif condition="$refund_list['showStatus'] eq 4"/><span>{pigcms{:L_("您取消申请退款")} <php>if (!empty($finishRefunds['reply_content'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$finishRefunds['reply_content']}</strong><php>}</php></span>
                    </if>
                    {pigcms{$rowset.applytime|date="Y-m-d H:i",###}
                </th>
            </tr>
            <tr>
                <th colspan="4">{pigcms{:L_("退款操作")}: <input type="text" name="reply_content" style="height: 24px;width:400px;" placeholder="{pigcms{:L_("请填写原因")}" /><button type="button" class="refund" data-type="agree" data-refund_price="{pigcms{$rowset['price']}" data-refund_id="{pigcms{$rowset['id']}">{pigcms{:L_("同意")}</button><button type="button" class="refund" data-type="disagree" data-refund_price="{pigcms{$rowset['price']}" data-refund_id="{pigcms{$rowset['id']}">{pigcms{:L_("不同意")}</button> </th>
            </tr>
    </volist>
    
<!--退款信息结束-->

    <tr>
        <th colspan="5">客户姓名：{pigcms{$user['nickname']}</th>
    </tr>
    <tr>
        <th colspan="5">客户手机：{pigcms{$user['phone']}</th>
    </tr>

    <tr>
        <th colspan="5">配送方式：<php>echo $order['is_pick_group'] == 1?'平台配送':'自提';</php></th>
    </tr>
    <!-- 自提人信息 -->
    <if condition="$order['pick_name']">
        <tr>
            <th colspan="5">提货人姓名：{pigcms{$order['pick_name']}</th>
        </tr>
        <tr>
            <th colspan="5">提货人手机号：{pigcms{$order['pick_phone']}</th>
        </tr>
    </if>

    <tr>
        <th colspan="5">下单时间：{pigcms{$order['add_time']|date="Y-m-d H:i:s",###} </th>
    </tr>
    <if condition="$order.get_start_time neq '00:00:00' && $order.get_end_time neq '00:00:00'">
    <tr>
        <th colspan="5">取货时间：{pigcms{$order['get_start_time']} - {pigcms{$order['get_end_time']}</th>
    </tr>
    </if>

    <tr>
        <th colspan="5">订单总金额：{pigcms{$order['total_money']|floatval} {pigcms{$config['Currency_txt']}</th>
    </tr>
    <if condition="$order.vip_discount_money gt 0">
        <tr>
            <th colspan="5">等级优惠金额：{pigcms{$order['vip_discount_money']|floatval} {pigcms{$config['Currency_txt']}</th>
        </tr>
    </if>
    <if condition="$order.coupon_price gt 0">
        <tr>
            <th colspan="5">优惠券优惠金额：{pigcms{$order['coupon_price']|floatval} {pigcms{$config['Currency_txt']}</th>
        </tr>
    </if>
    <if condition="$order.discount_price gt 0">
        <tr>
            <th colspan="5">优惠后金额：{pigcms{$order['discount_price']|floatval} {pigcms{$config['Currency_txt']}</th>
        </tr>
    </if>
    <tr>
        <th colspan="5">实付金额：{pigcms{$order['actual_discount_money']|floatval} {pigcms{$config['Currency_txt']}</th>
    </tr>



    <if condition="$order['score_used_count']">
    <tr>
        <th colspan="5">使用{pigcms{$config.score_name}：{pigcms{$order['score_used_count']} </th>
    </tr>
    <tr>
        <th colspan="5">{pigcms{$config.score_name}抵现：{pigcms{$config['Currency_symbol']}{pigcms{$order['score_deducte']|floatval} {pigcms{$config['Currency_txt']}</th>
    </tr>
    </if>
    
    <if condition="$order['merchant_balance']">
    <tr>
        <th colspan="5">商家余额：{pigcms{$config['Currency_symbol']}{pigcms{$order['merchant_balance']|floatval} {pigcms{$config['Currency_txt']}</th>
    </tr>
    </if>
    <!-- 平台E卡 -->
    <if condition="$order['pay_info']['use_ecard_price']">
    <tr>
        <th colspan="5">{pigcms{$config.ecard_alias_name}支付：{pigcms{$config['Currency_symbol']}{pigcms{$order['pay_info']['use_ecard_price']|floatval} {pigcms{$config['Currency_txt']}</th>
    </tr>
    </if>
    <if condition="$order['balance_pay']">
    <tr>
        <th colspan="5">平台余额：{pigcms{$config['Currency_symbol']}{pigcms{$order['balance_pay']|floatval} {pigcms{$config['Currency_txt']}</th>
    </tr>
    </if>
    <if condition="$order['payment_money']">
        <tr>
            <th colspan="5">在线支付：{pigcms{$config['Currency_symbol']}{pigcms{$order['payment_money']|floatval} {pigcms{$config['Currency_txt']}</th>
        </tr>
    </if>
    <if condition="$order['delivery_fee'] gt 0">
        <tr>
            <th colspan="5">配送费：{pigcms{$config['Currency_symbol']}{pigcms{$order['delivery_fee']|floatval} {pigcms{$config['Currency_txt']}</th>
        </tr>
    </if>


    <if condition="$order['pay_type']">
    <tr>
        <th colspan="5">订单支付方式：{pigcms{$order['pay_type']}</th>
    </tr>
    </if>
    
    <tr>
        <th colspan="5" style="color:red">
            订单状态：
            <if condition="$order.status eq 2 OR $order['status'] eq 13">
                <font color="green">已完成</font>
            <elseif condition="$order['status'] egt 1 AND $order['status'] lt 10 OR $order['status'] eq 13" />
                <font color="green">已支付</font>
            <elseif condition="$order['status'] eq 11 " />
                <font color="red">已取消</font>
            <elseif condition="$order['status'] eq 10 " />
                <font color="red">超时未支付</font>
            <elseif condition="$order['status'] eq 0 " />
                <font color="red">未支付</font>
            <elseif condition="$order['status'] eq 4 OR $order['status'] eq 12" />
                <font color="red">已退款</font>
            </if>
        </th>
    </tr>
    
    <tr>

        <th colspan="5" style="color:red">配送状态：
            <if condition="$order['status'] eq '1'">
                待发货
            <elseif condition="$order.status eq 7" />
                部分已发货
            <elseif condition="$order.status eq 5" />
                全部已发货
            <elseif condition="$order.status eq 8" />
                部分已到货
            <elseif condition="$order.status eq 6" />
                全部已到货（团长）
            <elseif condition="$order.status eq 3" />
                部分已自提
            <elseif condition="$order.status eq 2" />
                全部已自提
            <elseif condition="$order.status eq 13" />
                已评价
            <elseif condition="$order.status eq 11" />
                -
            </if>
        </th>
    </tr>
    <if condition="$order.village_group_type eq 2">
        <th colspan="5" style="color:red">配送状态(从团长处到用户)：
            <if condition="$order['order_status'] eq '0'">
                待发货
                <elseif condition="$order.order_status eq 1" />
                配送员接单
                <elseif condition="$order.order_status eq 2" />
                配送员取货
                <elseif condition="$order.order_status eq 3" />
                配送员配送中
                <elseif condition="$order.order_status eq 4" />
                配送员送达
                <else />
                -
            </if>
        </th>
    </if>
    
    <tr>
        <th colspan="5">备注:{pigcms{$order['note']|default="无"}</th>
    </tr>


    <if condition="$order['village_group_type'] eq 1">
        <tr>
            <th colspan="5" class="red">周期购配送相关信息</th>
        </tr>
        <tr>
            <th colspan="2">
                <span class="red">配送日期</span>
            </th>
            <th colspan="3">
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
                                已配送
                                <if condition="$item['real_send_time'] && $item['real_send_time'] lt $item['send_time']">
                                    （提前配送）
                                </if>
                                <elseif condition="$item['is_delay'] neq 1 && $item['status'] eq 0"/>
                                待配送
                                <elseif condition="$item['is_delay'] eq 1 || $item['status'] eq 6"/>
                                已延期
                            </if>
                        </div>
                        <div>{pigcms{$item['send_time']|date='Y-m-d',###}</div>
                    </div>
                </div>
            </th>

            <th colspan="3" class="delivery-progress">
                <div class="item <if condition="$item['is_delay'] eq 1">is-delayed<elseif condition="$item['status'] gt 0 && $item['status'] neq 6"/>is-delivery<else/>static</if>">

                <if condition="$item['real_send_time'] && $item['is_delay'] neq 1 && $item['status'] gt 0 && $item['status'] neq 6">
                    <div>{pigcms{$item['real_send_time']|date='Y-m-d H:i:s',###}</div>
                    <else/>
                    <div class="delay-btn fl">
                        <if condition="$item['is_delay'] neq 1">
                            <span class="delay_submit" data-detail_id="{pigcms{$item['id']}">点击延期</span>
                            <elseif condition="$item['is_delay'] eq 1 && $item['delay_user'] eq 0"/>
                            已延期
                            <elseif condition="$item['is_delay'] eq 1 && $item['delay_user'] eq 1"/>
                            团长已延期
                            <elseif condition="$item['is_delay'] eq 1 && $item['delay_user'] eq 2"/>
                            管理员已延期
                            <elseif condition="$item['is_delay'] eq 1 && $item['delay_user'] eq 3"/>
                            商家已延期
                        </if>
                    </div>
                </if>

            </th>
            </tr>
        </volist>
    </if>


</table>


<script>

    $('.delay_submit').click(function () {
        var detail_id = $(this).data('detail_id');
        layer.open({
            title:['提示','background-color:#FF658E;color:#fff;'],
            content:'<p style="width:180px;color:red;">确认延期该周期购吗？延期后不可撤销！</p>',
            btn: ['确定','取消'],
            yes: function(index){
                layer.close(index);
                $.post("{pigcms{:U('village_group_delay')}",{id:detail_id,is_header:1},function(data){
                    console.log('data',data);
                    if(data.status==1){
                        layer.open({
                            content: data.info
                            ,skin: 'msg'
                            ,anim: 'up'
                            ,time: 2
                        });
                        window.location.reload();
                    }else{
                        layer.open({
                            content: data.info
                            ,skin: 'msg'
                            ,anim: 'up'
                            ,time: 2
                        });
                    }
                },'json')
            }
        });
    });

    $('.refund').click(function(){
        var refund_id = $(this).data('refund_id'), type = $(this).data('type'), order_id = '{pigcms{$order["order_id"]}', refund_price=$(this).data('refund_price');
        var refund_price = $(this).data('refund_price');
        var reply_content = $(this).parent('th').find('input[name="reply_content"]').val();
        $.post("{pigcms{:U('Village_group/replyRefund')}", {'order_id':order_id, 'refund_price':refund_price,'refund_id':refund_id, 'reply_content':reply_content, 'type':type}, function(response){
            if (response.errcode == 1) {
                layer.msg(response.msg);
            } else {
                location.reload();
            }
        }, 'json');
    });
</script>
<include file="Public:footer"/>