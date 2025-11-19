<?php
/**
 * 企业员工
 * User: lumin
 */

namespace app\common\model\db;

use think\Model;

class QiyeStaff extends Model
{
    public function getOne($where = []){
        return $this->where($where)->find();
    }
}