<?php


namespace app\mall\model\db;


use think\Model;

class VillageGroupGoods extends Model
{
    public function merchantGoodsList($merId, $pageSize = 15, $params = [])
    {
        $prefix = config('database.connections.mysql.prefix');

        $result = $this->alias('s')
            ->join('merchant mt', 'mt.mer_id = s.mer_id')
            ->field(['s.goods_id', 's.name as goods_name', 's.price', '0 store_id'])//直播的社区商品列表
            ->where([
                's.mer_id'       => $merId,
                's.status'       => 1,
            ])
            ->where('s.goods_id', 'NOT IN', function ($query)use($prefix) {
                $query->table($prefix.'wechat_live_goods')
                    ->where('goods_type', 'village_group')
                    ->field('goods_id');
            });
        if(!empty($params['goods_name'])){
            $result = $result->where('s.name', 'like', "%{$params['goods_name']}%");
        }

        return $result->paginate(min($pageSize, 100));
    }
}