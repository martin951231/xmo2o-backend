<?php
/**
 * 外卖商品限时优惠
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/20 09:34
 */

namespace app\shop\model\db;
use think\Model;
class TimeLimitedDiscountGoods extends Model {
    /**
     * 获取商品限时优惠
     * @param $where
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDiscountByGoodsId($goodsId) {
       if(empty($goodsId)) {
            return false;
        }

        $nowTime = time();
        $week = date('w');
        $today = date('Y-m-d');
        $result = $this->where('is_del', '=', '0')
                       ->where('goods_id', '=', $goodsId)
                       ->where('end_date', '>=', $today)
                       ->where('start_date', '<=', $today)
                       ->whereRaw("UNIX_TIMESTAMP(CONCAT('$today', ' ', `end_time`)) > $nowTime AND UNIX_TIMESTAMP(CONCAT('$today', ' ', `start_time`)) < $nowTime AND FIND_IN_SET($week,`week`)")
                       ->find();
        return $result;
    }

    /**
     * 根据条件获取商品列表
     * @param $where array 条件
     * @param $field string 查询字段
     * @param $order array 排序
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGoodsListByJoin($where=[], $field, $order=[], $page='1', $limit='0') {

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');

        if($limit>0){
            $result = $this->alias('l')
                ->join($prefix.'shop_goods g','g.goods_id = l.goods_id')
                ->join($prefix.'merchant_store s','s.store_id = g.store_id')
                ->join($prefix.'merchant m','m.mer_id = s.mer_id')
                ->field($field)
                ->where($where)
                ->page($page,$limit)
                ->order($order)
                ->group('g.goods_id')
                ->select();
        }else{
            $result = $this->alias('l')
                ->join($prefix.'shop_goods s','s.goods_id = l.goods_id')
                ->join($prefix.'merchant_store s','s.store_id = g.store_id')
                ->join($prefix.'merchant m','m.mer_id = s.mer_id')
                ->field($field)
                ->where($where)
                ->order($order)
                ->group('g.goods_id')
                ->select();
        }
        return $result;
    }

    /**
     * 根据条件获取商品总数
     * @param $where array 条件
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGoodsCountByJoin($where=[]) {

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('l')
            ->join($prefix.'shop_goods g','g.goods_id = l.goods_id')
            ->join($prefix.'merchant_store s','s.store_id = g.store_id')
            ->join($prefix.'merchant m','m.mer_id = s.mer_id')
            ->where($where)
            ->group('g.goods_id')
            ->count();

        return $result;
    }
    /**
     * 根据id获取
     * @param $id
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDiscountById($id) {
        if(empty($id)) {
            return false;
        }

        $where = [
            'id' => $id
        ];
         
        $result = $this->where($where)->find();
        return $result;
     }

    
    /**
     * 根据id更新数据
     * @param $id
     * @param $data
     * @return array|bool|Model|null
     */
    public function updateById($id,$data) {
        if(!$id || $data){
            return false;
        }

        $where = [
            'id' => $id
        ];

        $result = $this->where($where)->update($data);
        return $result;
    }
}