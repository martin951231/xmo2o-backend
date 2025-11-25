<?php


namespace app\community\model\service;

use app\community\model\db\PlatOrder;

class PlatOrderService
{
    /**
     * 订单详情
     * @author lijie
     * @date_time 2020/10/29
     * @param $where
     * @return array|\think\Model|null
     */
    public function getPlatOrder($where)
    {
        $model_plat_order = new PlatOrder();
        $data = $model_plat_order->get_one($where);
        return $data;
    }

    public function addPlatOrder($data=[])
    {
        $model_plat_order = new PlatOrder();
        $id = $model_plat_order->add_order($data);
        return $id;
    }

    public function savePlatOrder($where=[],$data=[])
    {
        $model_plat_order = new PlatOrder();
        $id = $model_plat_order->save_one($where,$data);
        return $id;
    }
}