<?php
namespace app\community\model\db;

use think\Model;
class FlowChargingAuditLog extends Model{
    public function addOne($data)
    {
        $add = $this->insert($data);
        return $add;
    }
}