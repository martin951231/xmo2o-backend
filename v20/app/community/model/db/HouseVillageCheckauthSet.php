<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/11/12 19:24
 */
namespace app\community\model\db;

use think\Model;
class HouseVillageCheckauthSet extends Model
{
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        if (!$info || $info->isEmpty()) {
            $info = [];
        }else{
            $info=$info->toArray();
        }
        return $info;
    }

    public function saveOne($where,$data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }
    
    public function add($data){
        $result = $this->insertGetId($data);
        return $result;
    }
}