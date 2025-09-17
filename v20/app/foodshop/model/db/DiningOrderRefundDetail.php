<?php
/**
 * 餐饮订单退款详情model
 * Author: hengtingmei
 * Date Time: 2020/8/25 11:58
 */

namespace app\foodshop\model\db;
use think\Model;
class DiningOrderRefundDetail extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**退菜商品详情
     * @param $where
     * @param $field
     * @return array
     *
     */
    public function getRefundGoodList($where,$field)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('r')
            ->where($where)
            ->field($field)
            ->leftJoin($prefix.'foodshop_goods_library g','r.goods_id = g.goods_id')
            ->select();
        return $result;
    }

}