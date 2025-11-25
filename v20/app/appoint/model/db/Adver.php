<?php

declare (strict_types=1);

namespace app\appoint\model\db;

use app\common\model\db\Area;
use think\Model;

class Adver extends Model
{
    public function areaName()
    {
        return $this->belongsTo(Area::class, 'city_id', 'area_id');
    }

    public function getLastTimeAttr($value)
    {
        if(!empty($value)){
            return date('Y-m-d H:i:s', $value);
        }
        return '';
    }
}