<?php


namespace app\mall\model\service;


use app\mall\model\db\MallOrderDetail;

class MallOrderDetailService
{
    public function __construct()
    {
        $this->MallOderDatail = new MallOrderDetail();
    }

    public function getMallGoodByHot()
    {
        $this->MallOderDatail->getMallGoodByHot();
    }

    public function getByDetailId($id)
    {
        $one = $this->MallOderDatail->getByDetailId($id);
        return $one;
    }

    public function getByOrderId($field,$id)
    {
        $arr = $this->MallOderDatail->getByOrderId($field,$id);
        return $arr;
    }

    public function getOrderDetailGoods($order_id, $field, $gift = true){
        $where = [
            ['d.order_id', '=', $order_id]
        ];
        if($gift){
            $where[] = ['d.is_gift', '=', 0];
        }
        $data = $this->MallOderDatail->getGoodsJoinData($where, $field);
        if($data){
            return $data->toArray();
        }
        else{
            return [];
        }
    }

    /**
     * 获取用户已经参与过的商品级活动次数
     * @param  [type] $activity_type [description]
     * @param  [type] $activity_id   [description]
     * @param  [type] $uid           [description]
     * @return [type]                [description]
     */
    public function getActivityJoinNums($activity_type, $activity_id, $uid){
        if(empty($activity_id) || empty($activity_type) || empty($uid)){
            return 0;
        }
        $where = [
            ['o.uid', '=', $uid],
            ['d.activity_type', '=', $activity_type],
            ['d.activity_id', '=', $activity_id],
            ['o.status', '<', 50]
        ];
        $field = 'd.num';
        $data = $this->MallOderDatail->getOrderGoods($where, $field);
        if(empty($data)){
            return 0;
        }
        else{
            $data = $data->toArray();
            return array_sum(array_column($data, 'num'));
        }
    }
}
