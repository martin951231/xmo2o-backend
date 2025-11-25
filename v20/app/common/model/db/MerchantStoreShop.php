<?php
/**
 * 商家 店铺快店配置 model
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/6/1 14:55
 */

namespace app\common\model\db;

use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Model;

class MerchantStoreShop extends Model
{
    /**
     * 获取快店配置
     * User: chenxiang
     * Date: 2020/6/1 15:00
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     */
    public function getOne($where = [], $field = true)
    {
        $result = $this->field($field)->where($where)->find();
        return $result;
    }

    public function updateOne($where, $data)
    {
        return $this->where($where)->update($data);
    }
}