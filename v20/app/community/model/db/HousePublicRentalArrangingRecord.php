<?php
/**
 * @author : liukezhu
 * @date : 2022/9/2
 */

namespace app\community\model\db;

use think\Model;
class HousePublicRentalArrangingRecord extends Model
{

    public function getOne($where,$field=true,$order='id desc'){
        return $this->field($field)->where($where)->order($order)->find();
    }

    public function addOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }

    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    public function getCount($where) {
        $sql= $this->where($where)->count();
        return $sql;
    }

}