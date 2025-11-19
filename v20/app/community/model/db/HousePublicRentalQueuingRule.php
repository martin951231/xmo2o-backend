<?php
/**
 * @author : liukezhu
 * @date : 2022/8/3
 */

namespace app\community\model\db;

use think\Model;
class HousePublicRentalQueuingRule extends Model
{

    public function getOne($where,$field=true){
        return $this->field($field)->where($where)->find();
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

}