<?php

declare (strict_types=1);

namespace app\appoint\model\db;

use app\common\model\db\Area;
use think\Model;

class SliderCategory extends Model
{    
    public static function getSliderId()
    {
        return self::where(['cat_key' => 'wap_yue_slider'])->value('cat_id');
    }
}