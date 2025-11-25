<?php
/**
 * 商家抽成和分佣比率和积分设置
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/5/29 11:48
 */

namespace app\common\model\db;

use think\Model;

class MerchantPercentRate extends Model
{
    /**
     * 获取商家比例设置
     * User: chenxiang
     * Date: 2020/5/29 11:50
     * @param array $where
     * @param bool $field
     * @return array|mixed|Model|null
     */
    public function getRateData($where = [], $field = true)
    {
        $result = $this->field($field)->where($where)->find();
        return $result;
    }
}