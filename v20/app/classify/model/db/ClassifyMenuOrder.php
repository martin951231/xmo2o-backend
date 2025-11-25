<?php

namespace app\classify\model\db;

use app\common\model\db\User;
use think\Model;

class ClassifyMenuOrder extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'uid');
    }

    public function searchNameAttr($query, $value)
    {
        $value && $query->where('name', 'like', '%' . $value . '%');
    }
    
    public function getExpireTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i', $value) : '';
    }

    public function searchUidAttr($query, $value)
    {
        $value && $query->where('uid', $value);
    }

    public function searchStatusAttr($query, $value)
    {
        if($value == 1){
            $query->where('expire_time', '>', time());
        }elseif($value == 2){
            $query->where('expire_time','<', time());
        }
    }

    public function searchStartTimeAttr($query, $value)
    {
        strtotime($value) && $query->where('expire_time', '>=', strtotime($value));
    }

    public function searchEndTimeAttr($query, $value)
    {
        strtotime($value) && $query->where('expire_time', '<=', strtotime($value));
    }
}