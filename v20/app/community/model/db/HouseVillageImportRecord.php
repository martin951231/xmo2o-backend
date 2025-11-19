<?php

/**
 * Author lkz
 * Date: 2023/1/11
 * Time: 14:47
 */

namespace app\community\model\db;
use think\Model;

class HouseVillageImportRecord extends Model
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
        $count =$this->where($where)->count();
        return $count;
    }


    public function addFind($data)
    {
        $id =$this->insertGetId($data);
        return $id;
    }

}