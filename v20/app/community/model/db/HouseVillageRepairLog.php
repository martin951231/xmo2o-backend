<?php
/**
 * 工单操作纪律
 * @author weili
 * @date 2020/10/23
 */

namespace app\community\model\db;

use think\Model;
class HouseVillageRepairLog extends Model
{
    /**
     * Notes: 添加一条数据
     * @param $data
     * @return int|string
     * @author: weili
     * @datetime: 2020/10/23 11:40
     */
    public function addData($data)
    {
        $res = $this->insert($data);
        return $res;
    }
}