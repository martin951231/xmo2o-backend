<?php
/**
 * 分销员分享表
 */

namespace app\life_tools\model\db;

use \think\Model;

class LifeToolsDistributionUserShare extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $pk = 'share_id';
}
