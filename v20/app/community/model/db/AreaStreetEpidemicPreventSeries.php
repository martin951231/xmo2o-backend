<?php
/**
 * @author : liukezhu
 * @date : 2022/4/26
 */
namespace app\community\model\db;

use think\Model;
class AreaStreetEpidemicPreventSeries extends Model{


    public function getCount($where){
        $count = $this->where($where)->count();
        return $count;
    }


    public function getOne($where,$field =true){
        $info = $this->where($where)->field($field)->find();
        return $info;
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


    public function addFind($data){
        $id =$this->insertGetId($data);
        return $id;
    }

    public function editFind($where,$data){
        $res = $this->where($where)->update($data);
        return $res;
    }

}