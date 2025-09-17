<?php

/**
 * 杉德分账
 */
class plan_sandpay_settlement extends plan_base
{
    public function runTask()
    {
        $settlementMod = M('Sandpay_settlement');
        $paymentMod = M('Payment_money_order');
        $tm = time();

        $where = [
            'is_settlement' => 0,
            'plan_time' => ['LT', $tm]
        ];
        $records = $settlementMod->where($where)->order('id ASC')->limit(10)->select();
        if (empty($records)) {
            return true;
        }

        import("@.ORG.pay.Sande");
        $pay = new Sande();
        foreach ($records as $r) {
            $paymentMoneyOrder = $paymentMod->where(['id' => $r['payment_id']])->find();
            $settleRs = $pay->executeAccount($r['order_no'], getFormatNumber($r['platform_per']), $r['sande_merchant_fz'], $r['merchant_per'], $r['old_age_statement_per'], $r['uid'], $paymentMoneyOrder);

            $update = [
                'is_settlement' => $settleRs ? 1 : 2,
                'settlement_time' => $tm
            ];
            $settlementMod->where(['id' => $r['id']])->save($update);
        }
        return true;
    }
}

