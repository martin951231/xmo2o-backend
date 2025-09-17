<?php
/**
 * @author : liukezhu
 * @date : 2022/7/22
 */

namespace app\community\model\db;
use think\Model;

class HouseVillageVacancyNum extends Model{

    public function getFind($where,$field=true,$order='id desc'){
        $data = $this->where($where)->field($field)->order($order)->find();
        return $data;
    }

    public function getVillageFind($where,$field){
        $info = $this->alias('a')
            ->leftJoin('house_village b','a.village_id = b.village_id')
            ->leftJoin('house_property_digit d','d.property_id = b.property_id')
            ->field($field)
            ->where($where)
            ->order('a.add_time DESC')
            ->find();
        return $info;
    }

    public function saveOne($where=[],$data=[])
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    public function getVillageEle($where,$field){
        $info = $this->alias('a')
            ->leftJoin('house_village b','a.village_id = b.village_id')
            ->field($field)
            ->where($where)
            ->order('a.add_time DESC')
            ->select();
        return $info;
    }
    public function getList($where,$field = true,$order=true,$page=0,$limit=20){
        $sql = $this->field($field)->where($where)->order($order);
        if($page) {
            $sql->page($page,$limit);
        }
        $result = $sql->select();
        return $result;
    }
    public function addFind($data){
        $id =$this->insertGetId($data);
        return $id;
    }


}