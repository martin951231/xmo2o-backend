<?php
/**
 * 套餐日志
 */

namespace app\community\model\db;

use think\Model;
class PackageLog extends Model
{
    /**
     * Notes: 插入数据
     * @param $data
     * @return int|string
     * @author: weili
     * @datetime: 2020/8/15 18:07
     */
    public function createLog($data)
    {
        $res = $this->insert($data);
        return  $res;
    }
}