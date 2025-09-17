<?php


namespace app\grow_grass\model\db;


use think\Model;

class GrowGrassFile extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function delData($where_del){
        $result = $this->where($where_del)->delete();
        return $result;
    }
}