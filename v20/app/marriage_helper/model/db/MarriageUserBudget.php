<?php
namespace app\marriage_helper\model\db;

use think\Model;

class MarriageUserBudget extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function del($where) {
        if(empty($where)) {
            return false;
        }

        $result = $this->where($where)->delete();
        return $result;
    }
}