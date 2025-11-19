<?php
/**
 * @author : liukezhu
 * @date : 2022/3/30
 */

namespace app\community\model\db;

use think\Model;

class HouseNewRepairWorksOrderTimely extends Model{

    public function addOne($data) {
        $order_id = $this->insertGetId($data);
        return $order_id;
    }

    public function get_one($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

}