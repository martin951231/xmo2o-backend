<?php
/**
 * 审核日志
 */

namespace app\common\model\db;
use think\Model;
class Audit extends Model {
    use \app\common\model\db\db_trait\CommonFunc;
}