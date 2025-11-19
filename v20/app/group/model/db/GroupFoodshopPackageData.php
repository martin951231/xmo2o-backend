<?php

/**
 * 餐饮订单套餐核销记录
 * Created by PhpStorm.
 * Author: 钱大双
 * Date Time: 2020年12月25日15:30:17
 */

namespace app\group\model\db;

use think\Model;

class GroupFoodshopPackageData extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**获取餐饮套餐核销记录
     * @param $where
     * @return array
     */
    public function getPackageVerificList($where)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('p')
            ->where($where)
            ->field('p.*,g.name')
            ->Join($prefix . 'foodshop_goods_package g', 'p.package_id = g.id', 'left')
            ->select();
        if (empty($result)) {
            return [];
        } else {
            return $result->toArray();
        }

    }
}