<?php


namespace app\mall\model\db;

use \think\Model;

class ShopGoods extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function getCount($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $count = $this->alias('a')
            ->join([$prefix.'shop_goods_sort' => 'b'], 'a.sort_id = b.sort_id', 'inner')
            ->join([$prefix.'merchant_store' => 'c'], 'a.store_id = c.store_id', 'inner')
            ->where($where)
            ->count();
        return $count;
    }
}