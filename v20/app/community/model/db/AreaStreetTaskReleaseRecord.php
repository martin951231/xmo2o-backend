<?php
/**
 * @author : liukezhu
 * @date : 2022/4/25
 */
namespace app\community\model\db;

use think\Model;
class AreaStreetTaskReleaseRecord extends Model{

    public function getList($where,$field='*',$order='r.id desc',$page=0,$limit=20){
        $sql = $this
            ->alias('r')
            ->leftJoin('area_street_workers w','w.worker_id = r.worker_id')
            ->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }


    public function getCount($where){
        $count = $this
            ->alias('r')
            ->leftJoin('area_street_workers w','w.worker_id = r.worker_id')
            ->where($where)->count();
        return $count;
    }

    public function getFindCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }


    public function getOne($where,$field =true){
        $info = $this->alias('t')->where($where)->field($field)->find();
        return $info;
    }


    public function addFind($data){
        $id =$this->insertGetId($data);
        return $id;
    }
}