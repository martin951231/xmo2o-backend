<?php


namespace app\mall\model\db;
use \think\Model;

class Area extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function getSome1($where = [], $field = true,$order=true,$page=0,$limit=0){
//    	$result = $this->field($field)->where($where)->order($order)->limit($page,$limit)->select();
//    	return $result;

        $sql = $this->field($field)->where($where)->order($order)->group('area_pid');
        if($limit)
        {
            $sql->limit($page,$limit);
        }
        $result = $sql->select();
        return $result;
    }
}