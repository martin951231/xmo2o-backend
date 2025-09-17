<?php
namespace app\new_marketing\model\db;

use think\Model;

class NewMarketingOrderType extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获取业务提成
     */
    public function getSumBusiness($where,$field){
        $prefix = config('database.connections.mysql.prefix');
        $join=$prefix.'new_marketing_order_type_person'.' bp';
        $result =$result = $this ->alias('p')
            ->field($field)
            ->join($join,'p.id = bp.type_id')
            ->where($where)
            ->sum($field);
        return $result;
    }

    /**
     * @param $where
     * @param $field
     * @param $order
     * @return mixed
     * 获得订单列表
     */
    public function getBusinessList($where,$field,$order){
        $prefix = config('database.connections.mysql.prefix');
        $join=$prefix.'new_marketing_order_type_person'.' bp';
        $result =$result = $this ->alias('p')
            ->field($field)
            ->join($join,'p.id = bp.type_id')
            ->where($where)
            ->order($order)
            ->select();
        return $result;
    }

    /**
     * @param $where
     * @param $field
     * @param $order
     * @return mixed
     * 获得订单列表一级数量
     */
    public function getBusinessListAndCount($where,$field="*",$order= []){
        $prefix = config('database.connections.mysql.prefix');
        $result =$result = $this ->alias('p')
            ->field($field)
            ->join($prefix.'new_marketing_order_type_person bp','p.id = bp.type_id')
            ->where($where);
            $list['count']=$result->count();
        $list['list']=$result->order($order)
            ->select();
        return $list;
    }

    // 物业订单列表
    public function getPropertyOrderList($where, $field, $order, $page, $pageSize) {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('g')
            ->where($where)
            ->field($field)
            ->group('g.id')
            ->order($order)
            ->leftJoin($prefix.'package_order a','a.order_id = g.order_id')
            ->leftJoin($prefix.'new_marketing_order_type_person b','b.type_id = g.id');
		$assign['count'] = $result->count();
        $assign['list'] = $result->page($page, $pageSize)
            ->select()
            ->toArray();
        return $assign;
    }

    // 店铺订单列表
    public function getShopOrderList($where, $field, $order, $page, $pageSize) {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('g')
            ->where($where)
            ->field($field)
            ->group('g.id')
            ->order($order)
            ->leftJoin($prefix.'new_marketing_order a','a.order_id = g.order_id')
            ->leftJoin($prefix.'merchant m','m.mer_id = a.mer_id')
            ->leftJoin($prefix.'new_marketing_order_type_person b','b.type_id = g.id')
            ->leftJoin($prefix.'new_marketing_order_type_artisan c','c.type_id = g.id');
		$assign['count'] = $result->count();
        $assign['list'] = $result->page($page, $pageSize)
            ->select()
            ->toArray();
        return $assign;
    }

    // 店铺订单列表
    public function getShopOrderSum($where, $field, $order) {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('g')
            ->where($where)
            ->field($field)
            ->group('g.id')
            ->order($order)
            ->leftJoin($prefix.'new_marketing_order a','a.order_id = g.order_id')
            ->leftJoin($prefix.'merchant m','m.mer_id = a.mer_id')
            ->leftJoin($prefix.'new_marketing_order_type_person b','b.type_id = g.id')
            ->leftJoin($prefix.'new_marketing_order_type_artisan c','c.type_id = g.id');
        $assign = $result
            ->select()
            ->toArray();
        return $assign;
    }

    //获取订单数量
    public function getOrderCount($where) {
        $prefix = config('database.connections.mysql.prefix');
        $count = $this->alias('b')
            ->leftJoin($prefix.'new_marketing_order o','o.order_id = b.order_id')
            ->where($where)
            ->count();
        return $count;
    }

    /**
     * 添加一条数据
     * @author:zhubaodi
     * @date_time: 2021/9/10 13:25
     */
    public function addOne($data)
    {
        $insert = $this->insertGetId($data);
        return $insert;
    }
}