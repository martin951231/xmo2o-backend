<?php
/**
 * 三方导入数据关联车场车位相关
 */

namespace app\community\model\db\third;

use think\Model;

class HouseVillageThirdImportPark extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;


    /**
     * 得到某个列的数组
     * @access public
     * @param array|object $where 字段名 多个字段用逗号分隔
     * @param string       $column 字段名 多个字段用逗号分隔
     * @param string       $key   索引
     * @return array
     */
    public function getColumn($where, $column, $key = '')
    {
        return $this->where($where)->column($column, $key);
    }
}