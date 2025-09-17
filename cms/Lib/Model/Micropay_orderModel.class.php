<?php

class Micropay_orderModel extends Model
{
    public function settlementToMerchantByOrderid($orderid)
    {
        $order = $this->where(['orderid' => $orderid])->find();
        if ($order && $order['status'] == 1) {
            $merchantMod = M('Merchant');
            $merchantMoneyListMod = M('Merchant_money_list');
            $merchantQiyeMoneyListMod = M('Merchant_qiye_money_list');
            $qiyeMoney = $order['qiye_money'];
            $balanceMoney = $order['balance_money'];
            $tm = time();

            $percent = D('Percent_rate')->get_newlandpos_rate($order['mer_id']);
            //企业预存款金额结算给商家企业余额
            if ($qiyeMoney > 0 && !$merchantQiyeMoneyListMod->where(['order_id' => $orderid, 'type' => 'micropay'])->find()) {
                if ($percent > 0) {
                    $systemTake = round($qiyeMoney * $percent / 100, 2);
                } else {
                    $systemTake = 0;
                }
                $money = $qiyeMoney - $systemTake;

                $merchantMod->where(['mer_id' => $order['mer_id']])->setInc('qiye_money', $money);
                $nowMerQiyeMoney = $merchantMod->where(['mer_id' => $order['mer_id']])->getField('qiye_money');

                $moneyList = [
                    'mer_id' => $order['mer_id'],
                    'order_id' => $orderid,
                    'num' => 1,
                    'type' => 'micropay',
                    'money' => $money,
                    'system_take' => $systemTake,
                    'percent' => $percent,
                    'total_money' => $order['total_fee'],
                    'income' => 1,
                    'use_time' => $tm,
                    'desc' => '用户到店支付计入收入【新大陆：' . $order['device_name'] . '】',
                    'now_mer_qiye_money' => $nowMerQiyeMoney,
                ];
                $merchantQiyeMoneyListMod->add($moneyList);
            }

            //平台余额结算给商家余额
            if ($balanceMoney > 0 && !$merchantMoneyListMod->where(['order_id' => $orderid, 'type' => 'micropay'])->find()) {
                if ($percent > 0) {
                    $systemTake = round($balanceMoney * $percent / 100, 2);
                } else {
                    $systemTake = 0;
                }
                $money = $balanceMoney - $systemTake;

                $merchantMod->where(['mer_id' => $order['mer_id']])->setInc('money', $money);
                $nowMerMoney = $merchantMod->where(['mer_id' => $order['mer_id']])->getField('money');

                $moneyList = [
                    'mer_id' => $order['mer_id'],
                    'order_id' => $orderid,
                    'num' => 1,
                    'type' => 'micropay',
                    'money' => $money,
                    'system_take' => $systemTake,
                    'percent' => $percent,
                    'total_money' => $order['total_fee'],
                    'income' => 1,
                    'use_time' => $tm,
                    'desc' => '用户到店支付计入收入【新大陆：' . $order['device_name'] . '】',
                    'now_mer_money' => $nowMerMoney,
                ];
                $merchantMoneyListMod->add($moneyList);
            }
        }
    }
}