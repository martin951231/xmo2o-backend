<?php

class Mall_orderModel extends Model
{
    // 分发用户三级分佣获得的金额
    public function spreadSend($now_order)
    {

        // 类型
        $from_type = 'mall';

        // 分佣列表
        $spread_condition['order_type'] = $from_type;
        $spread_condition['order_id'] = $now_order['order_id'];
        $spread_condition['status'] = 0;//未到账
        $now_spread = D('User_spread_list')->where($spread_condition)->select();

        fdump(D('User_spread_list')->_sql(), 'plan_mall_order_spread', 1);
        fdump($now_spread, 'plan_mall_order_spread', 1);
        // 无分佣记录 直接返回
        if (!$now_spread) {
            // 将订单状态改为已分佣
            M('Mall_order')->where(['order_id' => $now_order['order_id']])->setField('is_spread', 1);
            return true;
        }


        if (C('config.open_score_pay_back') && C('config.system_take_spread_percent') > 0) {
            // 退款金额
            $refundMoney = $now_order['money_refund'];

            $percent = D('Percent_rate')->get_percent($now_order['mer_id'], $from_type, $refundMoney, '', $now_order['store_id'], true);
            $spread_total_money = getFormatNumber($refundMoney * $percent / 100);
        } else {
            // 退款金额
            $refundMoney = $now_order['money_refund'];

            $spread_total_money = getFormatNumber($refundMoney);
        }

        // 三级分佣比例
        $spread_rate = D('Percent_rate')->get_user_spread_rate($now_order['mer_id'], $from_type, $now_order['order_id']);

        // 重新计算分佣金额 然后给用户分发佣金
        foreach ($now_spread as $v) {
            switch ($v['level']) {
                case '1':
                    $user_spread_rate = $spread_rate['first_rate'];
                    break;
                case '2':
                    $user_spread_rate = $spread_rate['second_rate'];
                    break;
                case '3':
                    $user_spread_rate = $spread_rate['third_rate'];
                    break;
            }

            if ($spread_rate['goods_percent'] == 1) { // 开启每个商品单独佣金
                $spread_money = $user_spread_rate;
            } else {
                $spread_money = round(($v['money'] - $spread_total_money * $user_spread_rate / 100), 2);
            }

            $spread_money < 0 ? $spread_money = 0 : $spread_money = $spread_money;
            // 保存现在应得的金额
            $save_date = ['money' => $spread_money, 'old_money' => $v['money']];
            fdump('save_date', 'plan_mall_order_spread', 1);
            fdump($save_date, 'plan_mall_order_spread', 1);
            if (D('User_spread_list')->where(['pigcms_id' => $v['pigcms_id']])->save($save_date)) {
                // 分发佣金
                D('Merchant_money_list')->spread_check($v['uid'], $v['pigcms_id'], 2);
            }
        }

        // 将订单状态改为已分佣
        D('Mall_order')->where(['order_id' => $now_order['order_id']])->setField('is_spread', 1);
        return true;
    }
}