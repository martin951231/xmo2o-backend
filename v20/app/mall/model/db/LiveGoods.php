<?php
/**
 * LiveGoods.php
 * 商城首页装修获取直播和短视频商品
 * Create on 2020/12/11 11:51
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use think\Model;

class LiveGoods extends Model
{
    /**
     * @param $where
     * @param $field
     * zhumengqun
     */
    public function getLiveGoods($where, $field, $page, $pageSize)
    {
        $prefix = config('database.connections.mysql.prefix');

        if(empty($page)){
            $arr = $this->alias('a')
                ->join($prefix . 'mall_goods b', 'a.business_id = b.goods_id')
                ->field($field)
                ->where($where)
                ->select();
        }else{
            $arr = $this->alias('a')
                ->join($prefix . 'mall_goods b', 'a.business_id = b.goods_id')
                ->field($field)
                ->where($where)
                ->page($page, $pageSize)
                ->select();
        }
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $where
     * @param $field
     * zhumengqun
     */
    public function getLiveGoodsCount($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $count = $this->alias('a')
            ->join($prefix . 'mall_goods b', 'a.business_id = b.goods_id')
            ->where($where)
            ->count();
        return $count;
    }
}