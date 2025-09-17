<?php
/**
 * ShopGoodsGroupList.php
 * 文件描述
 * Create on 2021/3/11 14:59
 * Created by zhumengqun
 */

namespace app\common\model\db;

use think\Model;

class ShopGoodsGroupList extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     * @throws \Exception
     * 获取商品
     */
    public function getGoods($where, $field,$where1=[])
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('l')
            ->field($field)
            ->join($prefix . 'shop_goods g', ' l.goods_id = g.goods_id')
            ->where($where)
            ->where($where1)
            ->select()
            ->toArray();
        return $arr;

    }
}