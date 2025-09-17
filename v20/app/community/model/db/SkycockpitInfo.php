<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/11/12 19:24
 */
namespace app\community\model\db;

use think\Model;
class SkycockpitInfo extends Model
{
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
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

}