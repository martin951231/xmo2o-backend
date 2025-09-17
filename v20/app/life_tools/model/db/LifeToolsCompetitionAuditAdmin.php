<?php
/**
 * 体育赛事活动审核model
 */

namespace app\life_tools\model\db;

use app\common\model\db\Admin;
use \think\Model;

class LifeToolsCompetitionAuditAdmin extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }
}