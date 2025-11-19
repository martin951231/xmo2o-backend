<?php
/**
 * @author : liukezhu
 * @date : 2022/7/6
 */

namespace app\community\model\db;

use think\Model;

class HouseNewRepairGraborderNoticeRecord extends Model
{

    public function addAll($data)
    {
        $id = $this->insertAll($data);
        return $id;
    }

}