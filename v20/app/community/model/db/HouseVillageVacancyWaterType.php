<?php
/**
 * @author : liukezhu
 * @date : 2022/8/2
 */
namespace app\community\model\db;
use think\Model;

class HouseVillageVacancyWaterType extends Model{


    public function getFind($where,$field=true,$order='id desc'){
        $data = $this->where($where)->field($field)->order($order)->find();
        return $data;
    }

    public function saveOne($where=[],$data=[])
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    public function addAll($data){
        return $this->insertAll($data);
    }
    
}