<?php
/**
 * 系统后台可视化页面 外卖首页-限时秒杀功能 分类绑定的商品
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/12/21
 */

namespace app\common\model\db;
use think\Model;
class ShopSeckillCategoryGoods extends Model {
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 根据条件获取商品列表
     * @param $where array 条件
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
            $result = $this->alias('b')
                ->join($prefix.'shop_goods g','g.goods_id = b.goods_id')
                ->leftjoin($prefix.'time_limited_discount_goods l','l.goods_id = b.goods_id AND l.is_del=0')
                ->join($prefix.'merchant_store s','s.store_id = g.store_id')
                ->join($prefix.'merchant m','m.mer_id = s.mer_id')
                ->leftjoin($prefix.'shop_seckill_category_city c','c.cat_id = b.cat_id')
                ->field($field)
                ->where($where)
                ->group('b.id')
                ->page($page,$limit)
                ->order($order)
                ->select();
        }else{
            $result = $this->alias('b')
                ->join($prefix.'shop_goods s','s.goods_id = b.goods_id')
                ->leftjoin($prefix.'time_limited_discount_goods l','l.goods_id = b.goods_id AND l.is_del=0')
                ->join($prefix.'merchant_store s','s.store_id = g.store_id')
                ->join($prefix.'merchant m','m.mer_id = s.mer_id')
                ->leftjoin($prefix.'shop_seckill_category_city c','c.cat_id = b.cat_id')
                ->field($field)
                ->where($where)
                ->group('b.id')
                ->order($order)
                ->select();
        }
        return $result;
    }

    /**
     * 根据条件获取商品总数
     * @param $where array 条件
     * @param $order array 排序
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGoodsCountByJoin($where=[]) {

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('b')
            ->join($prefix.'shop_goods g','g.goods_id = b.goods_id')
            ->leftjoin($prefix.'time_limited_discount_goods l','l.goods_id = b.goods_id AND l.is_del=0')
            ->join($prefix.'merchant_store s','s.store_id = g.store_id')
            ->join($prefix.'merchant m','m.mer_id = s.mer_id')
            ->leftjoin($prefix.'shop_seckill_category_city c','c.cat_id = b.cat_id')
            ->group('b.id')
            ->where($where)
            ->count();

        return $result;
    }
}