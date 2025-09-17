<?php


namespace app\mall\model\db;

use think\Model;
class MallNewPeriodicPurchase extends Model
{
    /** 添加数据 获取插入的数据id
     * Date: 2020-10-22
     * @param $data
     * @return int|string
     */
    public function addPeriodic($data) {
        return $this->insertGetId($data);
    }


    /** 修改数据
     * Date: 2020-10-22
     * @param $data
     * @return int|string
     */
    public function updatePeriodic($data,$where) {
        $result = $this->where($where)->update($data);
        return $result;
    }

    /**
     * @param $where
     * @return array
     * 查询活动基本信息
     */
    public function getInfo($where,$field='*')
    {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('s')
            ->join($prefix.'mall_activity'.' m','s.id = m.act_id')
            ->where($where)
            ->field($field)
            ->find();
        if(!empty($result)){
            $result=$result->toArray();
        }else{
            $result=[];
        }
        return $result;
    }

    /**
     * @param $where
     * @param string $field
     * @return mixed
     * @author mrdeng
     * 获取周期购订单配送信息
     */
    public function getOrderInfo($where,$field='*')
    {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('s')
            ->join($prefix.'mall_new_periodic_purchase_order'.' m','s.id = m.act_id')
            ->join($prefix.'mall_periodic_deliver'.' d','d.order_id = m.order_id')
            ->where($where)
            ->field($field)
            ->order('d.id desc')
            ->find();
        if(empty($result)){
            $result=[];
        }else{
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * @param $where
     * @return array
     * 活动及期数记录信息
     */
    public function getPeriodicPurchaseAndOrderList($where){
        $field='s.id as purchase_order_id,m.periodic_count as current_periodic,s.periodic_date as date_num,m.*,s.*';
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('s')
            ->join($prefix.'mall_new_periodic_purchase_order'.' m','s.id = m.act_id')
            ->where($where)
            ->field($field)
            ->order('m.periodic_date asc')
            ->select();
        if(empty($result)){
            $result=[];
        }else{
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * @param $where
     * @return array
     * 活动及期数记录信息
     */
    public function getPeriodicPurchaseAndOrder($where,$where_bet=[],$status=3){
        $field='m.*,s.*,s.periodic_date as date_num,m.periodic_date as periodic_date,m.id as purchase_order_id,m.periodic_count as current_periodic';
        $prefix = config('database.connections.mysql.prefix');
        if($status==3){
                $result = $this->alias('s')
                    ->join($prefix.'mall_new_periodic_purchase_order'.' m','s.id = m.act_id')
                    ->where($where)
                    ->field($field)
                    ->order('m.periodic_date asc')
                    ->limit(1)
                    ->select();
        }else{
            $result = $this->alias('s')
                ->join($prefix.'mall_new_periodic_purchase_order'.' m','s.id = m.act_id')
                ->where($where)
                ->field($field)
                ->order('m.periodic_date asc')
                ->select();
        }
        if(empty($result)){
            $result=[];
        }else{
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * @param $where
     * @return array
     * 活动及期数记录信息
     */
    public function getPeriodicPurchaseAndOrderMall($where){
        $field='m.*,s.*,s.periodic_date as date_num,m.periodic_date as periodic_date,m.id as purchase_order_id,m.periodic_count as current_periodic';
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('s')
            ->join($prefix.'mall_new_periodic_purchase_order'.' m','s.id = m.act_id')
            ->where($where)
            ->field($field)
            ->order('m.periodic_date asc')
            ->select();
        if(empty($result)){
            $result=[];
        }else{
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * @param $where
     * @return array
     * 活动及期数记录信息
     */
    public function getPeriodicPurchaseAndOrderDetail($where){
        $field='m.*,s.*,m.periodic_date as periodic_date,s.periodic_date as date_num,m.id as purchase_order_id,m.periodic_count as current_periodic';
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('s')
            ->join($prefix.'mall_new_periodic_purchase_order'.' m','s.id = m.act_id')
            ->where($where)
            ->field($field)
            ->find();
        if(empty($result)){
            $result=[];
        }else{
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * @param $where 条件
     * @return array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 返回周期购条件个数
     */
    public function getPeriodicCount($where){
        $result=$this->where($where)->count();
        return $result;
    }

    /**
     * @param $where 条件
     * @param string $field 返回的字段
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 与商品表查询周期购活动信息
     */
    public function getGoodsAndPeriodic($where,$field="*"){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('s')
            ->join($prefix.'mall_goods'.' m','s.goods_id = m.goods_id')
            ->where($where)
            ->field($field)
            ->find();
        if(empty($result)){
            $result=[];
        }else{
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * @param $where 条件
     * @param string $field 返回的字段
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 单表查询周期购活动信息
     */
    public function getGoodsPeriodic($where,$field="*"){
        $result = $this->where($where)
            ->field($field)
            ->find();
        if(empty($result)){
            $result=[];
        }else{
            $result=$result->toArray();
        }
        return $result;
    }
}