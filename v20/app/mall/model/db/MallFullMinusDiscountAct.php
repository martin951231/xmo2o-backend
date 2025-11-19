<?php


namespace app\mall\model\db;

use think\Model;
use think\facade\Config;
class MallFullMinusDiscountAct extends Model
{
    /**
     * @param $condition
     * @return mixed
     * 根据条件活动活动详情
     */
    public function getDetail($condition){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('s')
            ->join($prefix.'mall_full_minus_discount_act_goods'.' m','s.id = m.act_id')
            ->join($prefix.'mall_goods'.' g','g.goods_id = m.goods_id')
            ->where($condition)
            ->find()
            ->toArray();
        return $result;
    }

    /**
     * @param $act_id
     * @return array
     * 满减折活动列表
     */
    public function getMallFullMinusDiscountGoodsList($act_id){
        $prefix = config('database.connections.mysql.prefix');
        $condition1[]=['s.id','=',$act_id];
        $condition1[]=['m.start_time','<',time()];
        $condition1[]=['m.end_time','>=',time()];
        $condition1[]=['m.type','=','minus_discount'];
        $condition1[]=['m.status','=',1];
        $result = $this ->alias('s')
            ->field('l.level_money,l.level_discount,s.is_discount')
            ->join($prefix.'mall_activity'.' m','s.id = m.act_id')
            ->join($prefix.'mall_full_minus_discount_level'.' l','s.id = l.act_id')
            ->where($condition1)
            ->order('l.level_sort asc')
            ->select();
        if(empty($result)){
            return [];
        }else{
            $result=$result->toArray();
            return $result;
        }

        //return $result;
    }

    /**
     * @param $act_id
     * @return mixed
     * 商品id集合
     */
    public function getGoodsID($act_id)
    {
        $field="s.goods_id";
        $result['goods_ids']=(new MallFullMinusDiscountActGoods())->getGoodsID($act_id,$field);//商品id列表
        return $result;
    }

    /**
     * @param $act_id
     * @return mixed
     * 商品id集合
     */
    public function getGoodsIDs($act_id)
    {
        $field="s.goods_id";
        $result['goods_ids']=(new MallFullMinusDiscountActGoods())->getGoodsID($act_id,$field);//商品id列表
        return $result;
    }

    /** 添加数据 获取插入的数据id
     * Date: 2020-10-19
     * @param $data
     * @return int|string
     */
    public function addFullMinusDiscount($data) {
        return $this->insertGetId($data);
    }


    /** 修改数据
     * Date: 2020-10-19
     * @param $data
     * @return int|string
     */
    public function updateFullMinusDiscount($data,$where) {
        $result = $this->where($where)->update($data);
        return $result;
    }

    /** 查询数据
     * Date: 2020-10-19
     * @param $data
     * @return int|string
     */
    public function getFullMinusDiscount($where) {
        $result = $this->where($where)->find();
        if(!empty($result)){
            $result=$result->toArray();
        }
        return $result;
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
            ->join($prefix.'mall_activity'.' m','s.id = m.act_id')
            ->field($fields)
            ->where($where)->find();
        if(!empty($result)){
            $result=$result->toArray();
        }
        return $result;
    }

}