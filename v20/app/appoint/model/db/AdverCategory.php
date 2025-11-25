<?php

declare (strict_types=1);

namespace app\appoint\model\db;

use think\Model;

class AdverCategory extends Model
{
    public static function getAdverId()
    {
        return self::where(['cat_key' => 'wap_yue_index_top'])->value('cat_id');
    }
}