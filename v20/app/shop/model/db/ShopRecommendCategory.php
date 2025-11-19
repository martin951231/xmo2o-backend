<?php
/**
 * 外卖订单详情推荐商品组-分类
 */

namespace app\shop\model\db;
use think\Model;
use think\facade\Env;
class ShopRecommendCategory extends Model {
    use \app\common\model\db\db_trait\CommonFunc;

    public function getList($where,$field=true)
    {
        $data = $this->where($where)->field($field)->select()->toArray();
        return $data;
    }
    
    public function getListByCategory($where,$whereOr,$field)
    {
        $prefix = config('database.connections.mysql.prefix');
        $list = $this->alias('a')
            ->join($prefix.'shop_recommend b','a.recommend_id = b.id')
            ->where($where)
            ->where(function ($query)use($whereOr){
                $query->whereOr($whereOr);
            })
            ->field($field)
            ->group('b.id')
            ->select()
            ->toArray();
        return $list;
    }
}