<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/11/12 19:24
 */
namespace app\community\model\db;

use think\Model;
class HouseVillageNewtaskLog extends Model
{
    public function getOneData($where,$field =true,$order='id desc'){
        $info = $this->field($field)->where($where)->order($order)->find();
        if (!$info || $info->isEmpty()) {
            $info = [];
        }else{
            $info=$info->toArray();
        }
        return $info;
    }
    //添加数据
    public function  addOneData($datas=array()){
        if(empty($datas)){
            return false;
        }
        $idd=$this->insertGetId($datas);
        return $idd;
    }

    //更新数据
    public function updateOneData($where=array(),$updateData=array()){
        if(empty($where) || empty($updateData)){
            return false;
        }
        $ret=$this->where($where)->update($updateData);
        return $ret;
    }

    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }
}