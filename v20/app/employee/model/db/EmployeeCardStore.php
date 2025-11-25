<?php


namespace app\employee\model\db;


use think\Model;

class EmployeeCardStore extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 删除
     */
    public function delData($where)
    {
        $ret=$this->where($where)->delete();
        return $ret;
    }
}