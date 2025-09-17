<?php
/**
 * 获取系统配置项
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/29 20:59
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;
class ConfigData extends Model{

    public function get_one($where=array()){
        $info = $this->field('*')->where($where)->find();
        return $info;
    }

    public function addConfig($addData=array()){
        if(!empty($addData)){
            $res = $this->insertGetId($addData);
            return $res;
        }
        return false;
    }

    public function updateConfig($where=array(),$saveData=array()){
        if(empty($where) || empty($saveData)){
            return false;
        }
        $info =$this->where($where)->update($saveData);
        return $info;
    }
}