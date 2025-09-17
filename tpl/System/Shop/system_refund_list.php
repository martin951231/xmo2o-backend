<include file="Public:header"/>
        <table cellpadding="0" cellspacing="0" class="frame_form" width="100%" style="margin: 0px 0px;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>退款金额</th>
                    <th>退款方式</th>
                   <!--  <th>平台余额</th>
                    <th>商家余额</th>
                    <th>会员卡赠送的余额</th>
                    <th>在线支付</th>
                    <th>退回的积分数</th>
                    <th>积分抵扣的金额数</th> -->
                    <th>退款原因</th>
                    <th>退款说明</th>
                    <th>退款时间</th>
                    <th>操作人</th>
                </tr>
            </thead>

            <volist name="refund_list" id="vo">
                <tr>
                    <td>{pigcms{$vo.id}</td>
                    <td>{pigcms{$vo.price}</td>
                    <td><if condition="$vo['refund_type'] eq 0">平台余额<else/>原路退回</if></td>
                   <!--  <td>{pigcms{$vo.balance_pay}</td>
                    <td>{pigcms{$vo.merchant_balance}</td>
                    <td>{pigcms{$vo.card_give_money}</td>
                    <td>{pigcms{$vo.payment_money}</td>
                    <td>{pigcms{$vo.score_used_count}</td>
                    <td>{pigcms{$vo.score_deducte}</td> -->
                    <td>{pigcms{$refund_reason[$vo['reason']]}</td>
                    <td>{pigcms{$vo.remark}</td>
                    <td>{pigcms{$vo.refund_time|date="Y-m-d H:i:s",###}</td>
                    <td>{pigcms{$vo.account}</td>
                </tr>
            </volist>
        </table>
<include file="Public:footer"/>