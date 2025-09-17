<?php 
namespace app\employee\model\db;
 
use think\Model;

class EmployeeCardUserPayCode extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $pk = 'pigcms_id';

    public function payLog()
    {
        return $this->belongsTo(EmployeeCardUserPayLog::class, 'code', 'code');
    }
}