<?php
/**
 * @author : liukezhu
 * @date : 2021/11/11
 */
namespace app\community\model\db;

use think\Model;

class HouseVillageExpressConfig extends Model{


    public function getOne($where,$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

}