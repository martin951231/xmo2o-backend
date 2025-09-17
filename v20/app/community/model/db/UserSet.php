<?php
/**
 * @author : liukezhu
 * @date : 2021/11/17
 */
namespace app\community\model\db;


use think\Model;
use think\facade\Db;
class UserSet extends Model{


    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    public function addOne($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }

    public function editFind($where,$data){
        $res = $this->where($where)->update($data);
        return $res;
    }
}