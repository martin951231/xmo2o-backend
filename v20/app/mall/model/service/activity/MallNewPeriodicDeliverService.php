<?php


namespace app\mall\model\service\activity;


use app\mall\model\db\MallNewPeriodicDeliver;

class MallNewPeriodicDeliverService
{
    //周期购订单配送期数
    /**
     * @param $order_id
     * @return array
     * @author mrdeng
     */
    public function getPeriodDeliverByOrderID($order_id,$goods_id){
       return (new MallNewPeriodicDeliver())->getPeriodDeliverByOrderID($order_id,$goods_id);
    }

}