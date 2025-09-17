<?php
/**
 * 设备绑定的相关信息
 */

namespace app\community\model\db;

use think\Model;

class DeviceBindInfo extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 得到某个列的数组
     * @param array|object  $where  查询条件
     * @param string        $field 字段名 多个字段用逗号分隔
     * @param string        $key   索引
     * @return array
     */
    public function getKeyColumn($where,$field = 'id',$key=''){
        return $this->where($where)->column($field,$key);
    }

    /**
     * @param array|object $where
     * @return bool
     * @throws \Exception
     */
    public function deleteInfo($where)
    {
        return $this->where($where)->delete();
    }
}