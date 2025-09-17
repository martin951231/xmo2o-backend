<?php


namespace app\mall\model\db;

use think\Model;
class MallFullMinusDiscountActGoods extends Model
{
    /**
     * @param $act_id
     * @return array
     * 商品列表
     */
    public function getGoodsList($act_id,$field){
        $condition[]=['s.act_id','=',$act_id];
        $prefix = config('database.connections.mysql.prefix');
        $result=$this ->alias('s')
            ->field($field)
            ->join($prefix.'mall_goods'.' g','s.goods_id = g.goods_id')
            ->where($condition)
            ->select()
            ->toArray();
        return $result;
    }


    public function getGoodsID($act_id,$field){
        $condition[]=['act_id','=',$act_id];
        $prefix = config('database.connections.mysql.prefix');
        $result=$this ->alias('s')
            ->field($field)
            ->join($prefix.'mall_goods'.' g','s.goods_id = g.goods_id')
            ->where($condition)
            ->column($field);
        if(empty($result)){
            return [];
        }
        return $result;
    }

    /**
     * @param $act_id
     * @param string $field
     * @return array
     * 活动商品id集合
     */
    public function getGoodsIDs($act_id,$field="goods_id"){
        $condition[]=['act_id','=',$act_id];
        $prefix = config('database.connections.mysql.prefix');
        $result=$this->where($condition)
            ->column($field);
        if(empty($result)){
            return [];
        }
        return $result;
    }

    /**批量添加商品信息
     * @param $data
     * @return int
     */
    public function addAll($data)
    {
        return $this->insertAll($data);
    }

    /**
     * 删除数据
     * @param $where
     * @return boolean
     */
    public function delActGoods($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * @param $where
     * @return array
     * 查询活动基本信息
     */
    public function getInfo($where,$fields='*')
    {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('s')
            ->field($fields)
            ->where($where)->select();
        if(!empty($result)){
            $result=$result->toArray();
        }
            return $result;
    }
}