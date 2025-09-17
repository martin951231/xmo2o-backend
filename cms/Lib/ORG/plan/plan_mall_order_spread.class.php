<?php

/**
 * author 钱大双
 * description 平台佣金获得按照实际7天后的剩余当前未退款的实际支付金额来算获得佣金
 * data 2020年11月25日17:39:26
 */
class plan_mall_order_spread extends plan_base
{
    public function runTask()
    {
        $time = time() - 86400 * 7;
        $limit = 200;
        $start = 0;

        $sql = 'select * from ' . C('DB_PREFIX') . 'mall_order where pay_time>0 AND pay_time<' . $time . ' AND status >=10 AND status <=49 AND is_spread=0 limit ' . $start . ',' . $limit;

        $list = M()->query($sql);
        while ($list) {
            $this->keepThread();
            foreach ($list as $key => $value) {
                $this->keepThread();
                // 获得三级分佣
                D('Mall_order')->spreadSend($value);
            }
            $start++;
            $sql = 'select * from ' . C('DB_PREFIX') . 'mall_order where pay_time>0 AND pay_time<' . $time . ' AND status >=10 AND status <=49  AND is_spread=0 limit ' . $start . ',' . $limit;
            $list = M()->query($sql);

        }

        return true;
    }
}

?>