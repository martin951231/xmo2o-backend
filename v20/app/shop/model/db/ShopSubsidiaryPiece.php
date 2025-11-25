<?php
/**
 * 外卖附属商品
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/19 09:35
 */

namespace app\shop\model\db;
use think\Model;
class ShopSubsidiaryPiece extends Model {
    /**
     * 根据商品获取附属菜列表
     * @param $where
     * @param $order
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSubsidiaryPieceByCondition($where, $order = []) {
       if(empty($where)) {
            return false;
        }

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('sp')
                    ->where($where)
                    ->rightJoin($prefix.'shop_subsidiary_piece_goods sug','sp.id = sug.piere_id')
                    ->rightJoin($prefix.'shop_goods g','sug.goods_id = g.goods_id')
                    ->field('
                        sp.id as id_sp,sp.name as name_sp,sp.maxnum as maxnum_sp,sp.mininum as mininum_sp,
                        sug.maxnum as maxnum_sug,sug.mininum as mininum_sug,sug.price as price_sug,sug.sort as sort_sug,
                        g.*
                    ')
                    ->order($order)
                    ->select();
        return $result;
    }
}