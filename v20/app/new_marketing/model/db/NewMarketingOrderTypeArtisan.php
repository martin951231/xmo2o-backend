<?php
/**
 * 汪晨
 * 2021/08/24
 */
namespace app\new_marketing\model\db;

use think\Model;

class NewMarketingOrderTypeArtisan extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    //获取总提成
    public function getArtisanCommission($where, $field) {
        $prefix = config('database.connections.mysql.prefix');
        $sum = $this->alias('a')
            ->leftJoin($prefix.'new_marketing_order_type b','a.type_id = b.id')
            ->leftJoin($prefix.'new_marketing_order o','b.order_id = o.order_id')
            ->where($where)
            ->sum($field);
        return $sum;
    }

    /**
     * Notes: 对应字段求和
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getSum($where,$field){
        $sum = $this->where($where)->sum($field);
        return $sum;
    }
}