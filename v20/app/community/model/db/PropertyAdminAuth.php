<?php


namespace app\community\model\db;

use think\Model;

class PropertyAdminAuth extends Model
{
    protected $pk = 'auth_id';
    public function getOne($where=[],$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

    //添加数据
    public function addData($addData=array()){
        if(empty($addData)){
            return false;
        }
        $idd=$this->insertGetId($addData);
        return $idd;
    }
    //修改数据
    public function editData($where=array(),$data=array())
    {
        $res = $this->where($where)->update($data);
        return $res;
    }
}