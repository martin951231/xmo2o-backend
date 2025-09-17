<?php
/**
 * MallRecommendGoods.php
 * 商城首页推荐模块关联商品model
 * Create on 2020/10/21 14:40
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use \think\Model;

class MallRecommendGoods extends Model
{
    /**
     * @param $where
     * @return array
     * 联合查询获取关联商品的详细
     */
    public function getDetail($where, $page, $pageSize, $order = 'id DESC',$whereOr=[])
    {
        $prefix = config('database.connections.mysql.prefix');
        $goods_area_list = [];
        if(cfg('open_mall_order_area')==1){
            $goods_area_list=(new MallGoodsArea())->getSome()->toArray();
        }
        if(empty($goods_area_list)) {
            $arr = $this->alias('r')
                ->field('g.*,r.*')
                ->join($prefix . 'mall_goods g', 'r.goods_id=g.goods_id')
                ->join($prefix . 'merchant_store s', 'g.store_id=s.store_id')
                ->where($where)
                ->order($order)
                ->page($page, $pageSize)
                ->select();
            if (!empty($arr)) {
                return $arr->toArray();
            } else {
                return [];
            }
        }else{
//            $where['ma.area_id']=0;
            $arr = $this->alias('r')
                ->field('g.*,r.*')
                ->join($prefix . 'mall_goods g', 'r.goods_id=g.goods_id')
                ->join($prefix . 'merchant_store s', 'g.store_id=s.store_id')
                ->join($prefix . 'mall_goods_area ma', 'ma.goods_id=r.goods_id')
                ->where($where);
            if($whereOr){
                $arr = $arr->where(function ($query)use($whereOr){
                    $query->where(['ma.area_id'=>0])->whereOr($whereOr);
                });
            }
            $arr = $arr->group('g.goods_id')
                ->order($order)
                ->page($page, $pageSize)
                ->select()
                ->toArray();
        }
        return $arr;
    }

    public function getCount($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        return $this->alias('r')
            ->field('g.*,r.*')
            ->join($prefix . 'mall_goods g', 'r.goods_id=g.goods_id')
            ->join($prefix . 'merchant_store s', 'g.store_id=s.store_id')
            ->where($where)
            ->count();
    }

    /**
     * @param $where
     * @return int
     * 获取推荐商品总数
     */
    public function getGoodsCount($where)
    {
        $total = $arr = $this->where($where)->count('goods_id');
        return $total;
    }

    /**
     * @param $where
     * @return int
     * 获取推荐商品总数
     */
    public function getGoodsRelateCount($where,$whereOr=[])
    {
        $prefix = config('database.connections.mysql.prefix');
        $goods_area_list = [];
        if(cfg('open_mall_order_area')==1){
            $goods_area_list=(new MallGoodsArea())->count();
        }
        if(empty($goods_area_list)) {
            $arr = $this->alias('r')
                ->join($prefix . 'mall_goods g', 'r.goods_id=g.goods_id')
                ->join($prefix . 'merchant_store s', 'g.store_id=s.store_id')
                ->where($where)
                ->count();
        }else{
//            $where['ma.area_id']=0;
            $arr = $this->alias('r')
                ->join($prefix . 'mall_goods g', 'r.goods_id=g.goods_id')
                ->join($prefix . 'merchant_store s', 'g.store_id=s.store_id')
                ->join($prefix . 'mall_goods_area ma', 'ma.goods_id=r.goods_id')
                ->where($where);
            if($whereOr){
                $arr = $arr->where(function ($query)use($whereOr){
                    $query->where(['ma.area_id'=>0])->whereOr($whereOr);
                });
            }
            $arr = $arr->group('g.goods_id')
                ->count();
        }
        return $arr;
    }

    /**
     * @param $field
     * @param $where
     * @return array
     * 根据条件获取
     */
    public function getByCondition($field, $where)
    {
        $arr = $this->field($field)->where($where)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $data
     * @return int|string
     * 添加
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * @param $data
     * @return int|string
     * 更新
     */
    public function updateOne($where, $data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }

    /**
     * @param $where
     * @return bool
     * @throws \Exception
     * 删除
     */
    public function delSome($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }
}