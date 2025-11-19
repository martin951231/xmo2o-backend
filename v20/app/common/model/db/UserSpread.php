<?php
/**
 * 用户推广关系 model
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/5/28 17:42
 */

namespace app\common\model\db;

use think\Model;

class UserSpread extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 获取用户推广关系
     * User: chenxiang
     * Date: 2020/5/28 17:49
     * @param bool $field
     * @param array $where
     * @return array|mixed|Model|null
     */
    public function getUserSpreadData($field = true, $where = [])
    {
        $result = $this->field($field)->where($where)->find();
        return $result;
    }

    /**
     * 更新某个字段
     * User: chenxiang
     * Date: 2020/6/1 14:01
     * @param array $where
     * @param string $field
     * @param string $value
     * @return mixed
     */
    public function setField($where = [], $field = '', $value = '') {
        $result = $this->where($where)->update([$field => $value]);
        return $result;
    }
}