<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/8/6 16:52
 */

namespace app\community\model\db;

use think\Model;
class PrivilegePackageContent extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * Notes: 获取某个字段值
     * @param $where
     * @param $field
     * @return array
     * @author: weili
     * @datetime: 2020/11/9 10:35
     */
    public function getColumn($where,$field)
    {
        $data = $this->where($where)->column($field);
        return $data;
    }
}