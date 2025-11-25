<?php
/**
 * 投票活动
 */
namespace app\community\model\db;

use think\Model;
class PluginVoteOption extends Model{
    public function getCount($where){
        $count = $this->where($where)->count();
        return $count;
    }


    public function getOne($where,$field =true){
        $info = $this->where($where)->field($field)->find();
        return $info;
    }
    
//    public function getData($where,$field =true,$order = ''){
//        $info = $this->where($where)->order($order)->field($field)->select();
//        return $info;
//    }

    public function getList($where,$field='*',$order='option_id desc'){
        $list = $this
            ->where($where)
            ->order($order)
            ->select()
            ->toArray();
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