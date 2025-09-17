<?php
declare (strict_types = 1);

namespace app\life_tools\model\db;

use think\Model;

/**
 * @mixin \think\Model
 * @property string $status 是否启用  0 不启用 1启用
 */
class LifeToolsScenicMap extends Model
{
    public function searchNameAttr($query, $value)
    {
       !empty($value) && $query->where('name','like', "%$value%");
    }

    public function scenic()
    {
        return $this->belongsTo(LifeTools::class,'scenic_id', 'tools_id');
    }

    public function mapLine()
    {
        return $this->hasMany(LifeToolsScenicMapLine::class,'map_id', 'id');
    }

    public function mapPlace()
    {
        return $this->hasMany(LifeToolsScenicMapPlace::class,'map_id', 'id');
    }
}
