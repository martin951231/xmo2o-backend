<?php
/**
 * 用户等级 model
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/6/1 14:35
 */

namespace app\common\model\db;

use think\Model;

class UserLevel extends Model
{
	use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 获取信息
     * User: chenxiang
     * Date: 2020/6/1 14:37
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     */
    public function getOne($where = [], $field = true)
    {
        $result = $this->field($field)->where($where)->find();
        return $result;
    }

}