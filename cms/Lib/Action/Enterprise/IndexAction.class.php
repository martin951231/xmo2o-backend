<?php

class IndexAction extends BaseAction
{
    public function index()
    {
        //数据统计
        $static = [];
        $static['merchant_count'] = M('Qiye_merchant')->where(['qiye_id' => $this->qiyeId])->count();
        $static['staff_count'] = M('Qiye_staff')->where(['qiye_id' => $this->qiyeId])->count();
        $static['staff_total_money'] = M('Qiye_staff')->where(['qiye_id' => $this->qiyeId])->sum('money');
        $static['not_settlement_money'] = M('Qiye')->where(['qiye_id' => $this->qiyeId])->getField('money');

        //实时概况
        //今天
        $today_zero = strtotime(date('Y-m-d'));
        $condition['_string'] = 'm.time>' . $today_zero . ' AND m.time <=' . ($today_zero + 86400) . " AND m.order_id > 0 AND s.qiye_id=" . $this->qiyeId;
        $join = 'INNER JOIN ' . C('DB_PREFIX') . 'qiye_staff AS s ON m.staff_id = s.staff_id ';
        $static['today_order_money'] = M('Qiye_staff_money_list')->alias('m')
            ->join($join)->where($condition)
            ->sum('m.money');
        $static['today_order_count'] = M('Qiye_staff_money_list')->alias('m')
            ->join($join)->where($condition)
            ->count();
        //昨天
        $condition['_string'] = 'm.time>' . ($today_zero - 86400) . ' AND m.time <=' . $today_zero . " AND m.order_id > 0 AND s.qiye_id=" . $this->qiyeId;
        $static['yesterday_order_money'] = M('Qiye_staff_money_list')->alias('m')
            ->join($join)->where($condition)
            ->sum('m.money');
        $static['yesterday_order_count'] = M('Qiye_staff_money_list')->alias('m')
            ->join($join)->where($condition)
            ->count();

        $this->assign('static', $static);
        $this->display();
    }


    public function getWeekOrderList()
    {
        $return['order_money_list'] = array();
        $return['order_count_list'] = array();
        for ($i = 6; $i >= 0; $i--) {
            if ($i == 0) {
                $today_zero = strtotime(date('Y-m-d'));
            } else {
                $today_zero = strtotime(date('Y-m-d 00:00:00', strtotime('-' . $i . ' day')));
            }
            $condition['_string'] = 'm.time>' . $today_zero . ' AND m.time <=' . ($today_zero + 86400) . " AND m.order_id > 0 AND s.qiye_id=" . $this->qiyeId;
            $join = 'INNER JOIN ' . C('DB_PREFIX') . 'qiye_staff AS s ON m.staff_id = s.staff_id ';

            $return['order_money_list'][] = floatval(M('Qiye_staff_money_list')->alias('m')->join($join)->where($condition)->sum('m.money'));
            $return['order_count_list'][] = intval(M('Qiye_staff_money_list')->alias('m')->join($join)->where($condition)->count());
            $return['xAxis_txt'][] = date('Y-m-d', $today_zero);
        }
        $this->success($return);
    }
}