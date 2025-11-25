<?php

declare (strict_types=1);

namespace app\appoint\model\db;

use think\Model;

class AppointRecommend extends Model
{
    public function appoint()
    {
        return $this->belongsTo(Appoint::class, 'appoint_id', 'appoint_id');
    }
    public function appointProduct()
    {
        return $this->hasMany(AppointProduct::class, 'appoint_id', 'appoint_id');
    }
}