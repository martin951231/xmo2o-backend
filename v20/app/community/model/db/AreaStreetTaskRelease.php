<?php
/**
 * @author : liukezhu
 * @date : 2022/4/24
 */
namespace app\community\model\db;

use think\Model;
class AreaStreetTaskRelease extends Model{

    public function getCount($where){
        $count = $this->alias('t')->where($where)->count();
        return $count;
    }


    public function getOne($where,$field =true){
        $info = $this->alias('t')->where($where)->field($field)->find();
        return $info;
    }

    public function getOneOr($where,$field =true){
        $info = $this->alias('t')->whereOr($where)->field($field)->find();
        return $info;
    }

    public function getList($where,$field='*',$order='id desc',$page=0,$limit=20){
        $sql = $this->alias('t')->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    public function getLists($where,$field=true,$order='t.id desc',$page=0,$limit=20){
        $sql = $this->alias('t')
            ->leftJoin('area_street_task_release_record r','t.id=r.task_id')
            ->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    public function getCounts($where){
        $count = $this->alias('t') ->leftJoin('area_street_task_release_record r','t.id=r.task_id')->where($where)->count();
        return $count;
    }


    public function addFind($data){
        $id =$this->insertGetId($data);
        return $id;
    }

    public function editFind($where,$data){
        $res = $this->alias('t')->where($where)->update($data);
        return $res;
    }

}