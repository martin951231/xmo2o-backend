<?php

namespace app\social_activity\model\db;

use think\Model;

class SocialActivityPointRule extends Model
{
    public function getRuleById($id)
    {
        return $this->where('id', $id)->find();
    }

    public function getStatusTextAttr($value, $data)
    {
        $statusMap = [
            2 => '禁止',
            1 => '正常',
        ];
        return $statusMap[$data['status']] ?? '未知';
    }

    public function searchStatusAttr($query, $value, $data)
    {
        if ($value) {
            $query->where('status', $value);
        }
    }

    public function searchStartTimeAttr($query, $value, $data)
    {
        if ($value) {
            $query->where('create_time', '>=', $value . ' 00:00:00');
        }
    }

    public function searchEndTimeAttr($query, $value, $data)
    {
        if ($value) {
            $query->where('create_time', '<=', $value . ' 23:59:59');
        }
    }
}
