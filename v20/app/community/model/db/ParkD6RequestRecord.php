<?php
/**
 * @author : liukezhu
 * @date : 2022/3/15
 */
namespace app\community\model\db;

use think\Model;
class ParkD6RequestRecord extends Model{


    public function add($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }


    public function getOne($where,$field=true)
    {
        $data = $this->field($field)->where($where)->find();
        return $data;
    }

    public function addAll($data){
        if(empty($data)){
            return 0;
        }
        return $this->insertAll($data);
    }

    public function edit($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }

}
