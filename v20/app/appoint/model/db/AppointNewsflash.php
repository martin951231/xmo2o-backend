<?php

declare (strict_types=1);

namespace app\appoint\model\db;

use think\Model;

class AppointNewsflash extends Model
{
    protected $globalScope = ['delete_time'];
    protected $json = ['appoint_json'];
    protected $jsonAssoc = true;

    public function scopeDeleteTime($query)
    {
        $query->where('delete_time',0);
    }
    
    public function searchTitleAttr($query, $value)
    {
        $value && $query->where('title', 'LIKE', "%{$value}%");
    }

    public function searchStatusAttr($query, $value)
    {
        $value >-1 && $query->where('status', $value);
    }
}