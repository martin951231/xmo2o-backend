<?php


namespace app\life_tools\model\db;

use think\Model;

class LifeToolsAppointVerify extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function order()
    {
        return $this->belongsTo(LifeToolsAppointJoinOrder::class, 'appoint_order_id', 'pigcms_id');
    }

    public function appoint()
    {
        return $this->belongsTo(LifeToolsAppoint::class, 'appoint_id', 'appoint_id');
    }

    public function getAddTimeTextAttr($value, $data)
    {
        return date('Y-m-d H:i', $data['add_time']);
    }
}