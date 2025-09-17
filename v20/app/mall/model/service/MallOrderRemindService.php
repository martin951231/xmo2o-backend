<?php


namespace app\mall\model\service;


use app\mall\model\db\MallOrderRemind;

class MallOrderRemindService
{
    //插入提醒（待发货，催单，售后）
    /**
     * @param $store_id int 门店id
     * @param $status string 待发货10 催单20 售后30
     * @return int|string 返回主键
     */
    public function insertRemind($store_id,$status, $order_id){
        if (empty($store_id) || empty($status) || empty($order_id)) {
            throw new \think\Exception('参数缺失');
        }
        $data['store_id']=$store_id;
        $data['last_uptime']=time();
        $data['status']=$status;
        $data['order_id']=$order_id;
        $id= (new MallOrderRemind())->addOne($data);//返回主键
        return $id;
    }

    //获取今天有没有提醒过
    public function getTodayRemind($store_id,$status, $order_id){
        if (empty($store_id) || empty($status) || empty($order_id)) {
            throw new \think\Exception('参数缺失');
        }
        $where = [
            ['store_id', '=', $store_id],
            ['status', '=', $status],
            ['order_id', '=', $order_id],
            ['last_uptime', 'between', [strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59'))]]
        ];
        $count = (new MallOrderRemind())->getCount($where);
        return $count;
    }
}