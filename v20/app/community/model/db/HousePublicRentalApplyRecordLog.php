<?php

namespace app\community\model\db;

use think\Model;
class HousePublicRentalApplyRecordLog extends Model
{

    public function getList($where,$field='*',$order='id desc',$page=0,$limit=20)
    {
        $sql = $this->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    public function getCount($where) {
        $sql= $this->where($where)->count();
        return $sql;
    }


    public function getOne($where,$field,$order='id desc'){
        return $this->field($field)->where($where)->order($order)->find();
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