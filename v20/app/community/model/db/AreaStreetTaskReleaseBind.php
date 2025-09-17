<?php
/**
 * @author : liukezhu
 * @date : 2022/4/25
 */
namespace app\community\model\db;

use think\Model;
class AreaStreetTaskReleaseBind extends Model{

    public function addAll($data){
        $res = $this->insertAll($data);
        return $res;
    }

    public function delFind($where){
        $res = $this->where($where)->delete();
        return $res;
    }


    public function getList($where,$field='*',$order='id desc',$page=0,$limit=20){
        $sql = $this->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }



    public function getColumn($where,$field)
    {
        $column = $this->alias('b')
//            ->rightJoin('house_village_grid_member m','m.workers_id=b.worker_id and m.area_id=b.street_id')
            ->where($where)->column($field);
        return $column;
    }


    public function getOne($where,$field =true){
        $info = $this->where($where)->field($field)->find();
        return $info;
    }

}