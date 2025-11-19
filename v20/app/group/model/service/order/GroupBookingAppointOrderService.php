<?php
/**
 * 场次预约订单service
 */

namespace app\group\model\service\order;

use app\group\model\db\GroupBookingAppointOrder;

class GroupBookingAppointOrderService
{
    public $groupBookingAppointOrderModel = null;
    public function __construct()
    {
        $this->groupBookingAppointOrderModel = new GroupBookingAppointOrder();
    }

    /**
    *插入一条数据
    * @param $data array 
    * @return array
    */
    public function add($data){
        if(empty($data)){
            return false;
        }
        $result = $this->groupBookingAppointOrderModel->add($data);
        
        return $result;
    }

    /**
     * 获取一条数据
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        $result = $this->groupBookingAppointOrderModel->getOne($where);
        if(!$result) {
            return [];
        }
        return $result;
    }

    /**
     * 获取当天场次预订销售数量
     * @param $ruleId
     * @param $date
     * @date: 2021/06/16
     */
    public function getDaySaleCountByRuleId($ruleId, $date)
    {
        $start = strtotime($date);
        $end = $start + 86400;
        $where = [
            ['r.rule_id', '=', $ruleId],
            ['b.book_start_time', '>=', $start],
            ['b.book_start_time', '<', $end],
            ['o.paid', '=', 1]
        ];
        return $this->groupBookingAppointOrderModel->alias('b')
            ->join('group_order o', 'o.order_id=b.order_id')
            ->join('group g', 'g.group_id=o.group_id')
            ->join('group_booking_appoint_rule r', 'r.group_id=g.group_id')
            ->where($where)
            ->count();
    }
    
}