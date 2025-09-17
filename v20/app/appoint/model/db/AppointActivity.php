<?php

declare (strict_types=1);

namespace app\appoint\model\db;

use app\common\model\db\MicroPageDecorate;
use app\common\model\db\MicroPageDecorateField;
use think\Model;

class AppointActivity extends Model
{
    public function microPage()
    {
        return $this->morphOne(MicroPageDecorate::class, ['source', 'source_id'], 'appoint_activity');
    }  
    
    public function microPageField()
    {
        return $this->morphOne(MicroPageDecorateField::class, ['source', 'source_id'], 'appoint_activity');
    }
}