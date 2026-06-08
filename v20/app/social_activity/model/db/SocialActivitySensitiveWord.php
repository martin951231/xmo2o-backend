<?php

namespace app\social_activity\model\db;

use think\Model;

class SocialActivitySensitiveWord extends Model
{
    public function getRuleById($id)
    {
        return $this->where('id', $id)->find();
    }

    public function getStatusTextAttr($value, $data)
    {
        $statusMap = [
            1 => '启用',
            2 => '禁用',
        ];
        return $statusMap[$data['status']] ?? '未知';
    }

    public function searchKeywordAttr($query, $value, $data)
    {
        if ($value !== '' && $value !== null) {
            $query->where('word', 'like', '%' . $value . '%');
        }
    }

    public function searchStatusAttr($query, $value, $data)
    {
        if ($value) {
            $query->where('status', $value);
        }
    }

    public function searchEndTimeAttr($query, $value, $data)
    {
        if (!empty($value)) {
            $query->where('create_time', '<=', $value . ' 23:59:59');
        }
    }

    public function searchStartTimeAttr($query, $value, $data)
    {
        if (!empty($value)) {
            $query->where('create_time', '>=', $value);
        }
    }
}
