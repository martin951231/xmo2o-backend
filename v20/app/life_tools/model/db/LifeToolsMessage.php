<?php
/**
 * 景区体育健身-消息
 */

namespace app\life_tools\model\db;

use \think\Model;

class LifeToolsMessage extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function tools()
    {
        return $this->belongsTo(LifeTools::class, 'tools_id', 'tools_id');
    }

}