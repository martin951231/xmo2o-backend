<?php


namespace app\merchant\model\db;

use think\Model;
class Keywords extends Model {
    use \app\common\model\db\db_trait\CommonFunc;
    public function getDel($where){
       return $this->where($where)->delete();
    }
}