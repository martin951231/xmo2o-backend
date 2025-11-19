<?php

namespace app\common\model\db;
use think\Model;
class UserImport extends Model {
    use \app\common\model\db\db_trait\CommonFunc;

    public function getUserImport($where = [], $field = true) {
        $result = $this->field($field)->where($where)->find();
        return $result;
    }
    
    public function saveUserImport($where, $data){
        $result = $this->where($where)->update($data);
        return $result;
    }
}