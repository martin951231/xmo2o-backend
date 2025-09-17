<?php


namespace app\mall\model\service\activity;


use app\mall\model\db\MallNewGroupOrder;

class MallNewGroupOrderService
{
    /**
     * @param $order_id
     * @return mixed
     * 获取拼团订单信息
     */
    public function getOrderMsg($order_id){
        $arr=(new MallNewGroupOrder())->getOne($order_id);
        return $arr;
    }

    /**
     * @param $tid
     * @param $user_id 拼主id
     */
    public function getOneOrder($tid,$user_id){
        $where=[['tid','=',$tid],['uid','=',$user_id]];
        $field="*";
        $arr=(new MallNewGroupOrder())->getOneOrder($where,$field);
        return $arr;
    }
}