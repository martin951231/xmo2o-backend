<?php


namespace app\store_marketing\model\service;


use app\merchant\model\db\MerchantStore;
use app\new_marketing\model\db\NewMarketingOrder;

class StoreMarketingOrderService
{
    public function getOrderList($param)
    {
        $where = ['uid' => $param['uid'], 'paid' => 1];
        $list = (new NewMarketingOrder())->getSome($where, true, 'add_time desc', ($param['page'] - 1) * $param['pageSize'], $param['pageSize']);
        $arr = [];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $data['orderid'] = $v['orderid'];
                $data['order_type'] = $v['order_type'];
                $data['order_id'] = $v['order_id'];
                $data['store_id'] = empty($v['store_id'])?$v['pack_name']:(new MerchantStore())->getIdsByWhere(['store_id'=>$v['store_id']],'name');
                $data['store_name'] = $v['store_name'];
                $data['buy_num'] = $v['buy_num'];
                $data['add_time'] = date("Y-m-d H:i:s",$v['add_time']);
                $data['total_price'] = get_format_number($v['total_price']);
                $arr[]=$data;
            }
        }
        $out['list']=$arr;
        return $out;
    }
}