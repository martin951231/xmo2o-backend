<?php
/**
 * @author : liukezhu
 * @date : 2022/5/9
 */
namespace app\community\model\db;

use think\Model;

class HouseVillageUserBindAttribute extends Model{

    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    public function getListByGroup($where,$group,$order='id desc',$field=true)
    {
        return $this->where($where)->field($field)->group($group)->order($order)->select();
    }

    public function getFind($where,$field=true,$order='id desc'){
        $data = $this->where($where)->field($field)->order($order)->find();
        return $data;
    }

    public function getFinds($where,$field=true,$order='a.id desc'){
        $data = $this->alias('a')
            ->leftJoin('house_village b','b.village_id = a.village_id')->where($where)->field($field)->order($order)->find();
        return $data;
    }

    public function addFind($data){
        $id =$this->insertGetId($data);
        return $id;
    }

    public function saveOne($where,$data){
        $res = $this->where($where)->save($data);
        return $res;
    }

}