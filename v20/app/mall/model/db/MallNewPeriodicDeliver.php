<?php


namespace app\mall\model\db;

use think\Model;
class MallNewPeriodicDeliver extends Model
{
    //周期购订单配送期数
    /**
     * @param $order_id
     * @return array
     * @author mrdeng
     */
    public function getPeriodDeliverByOrderID($order_id,$goods_id){
        $condition[]=['s.order_id','=',$order_id];
        $condition[]=['s.goods_id','=',$goods_id];
        $prefix = config('database.connections.mysql.prefix');
        $field='a.periodic_count,s.*';
        $result = $this ->alias('s')
            ->join($prefix.'mall_new_periodic_purchase'.' a','s.goods_id = a.goods_id')
            ->where($condition)
            ->field($field)
            ->select()
            ->toArray();
        return $result;
    }


    /**
     * @param $where array 条件
     * @return array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 返回周期购期数记录
     */
    public function getPeriodicDeliver($where){
        $result=$this->where($where)->find();
        if(empty($result)){
            return $result;
        }else{
            return $result->toArray();
        }
    }

    public function getPeriodicDeliver1($where2){
        $result=$this->where($where2)->order('id desc')->find();
        if(empty($result)){
            return $result;
        }else{
            return $result->toArray();
        }
    }
    /**
     * 添加一条数据
     * User: mrdeng
     * Date: 2020/10/26 16:56
     * @param $data
     * @return mixed
     */
    public function addPeriodicDeliver($data) {
        return $this->insertGetId($data);
    }

    /**
     * @param $where 条件
     *  @param $data 更新数据
     * @author mrdeng
     * @return array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 更新每一期配送记录
     */
    public function updatePeriodicDeliver($where,$data){
        $result=$this->where($where)->save($data);
        return $result;
    }

}