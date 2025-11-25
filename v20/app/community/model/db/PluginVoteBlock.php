<?php
/**
 * 投票活动
 */
namespace app\community\model\db;

use think\Model;
class PluginVoteBlock extends Model{
    public function getCount($where){
        $count = $this->where($where)->count();
        return $count;
    }


    public function getOne($where,$field =true){
        $info = $this->where($where)->field($field)->find();
        return $info;
    }
    
    public function getList($where,$field =true,$order = ''){
        $list = $this->alias('vb')
            ->join('plugin_vote_option vo','vo.block_id=vb.block_id AND vo.vote_id=vb.vote_id','left')
            ->where($where)
            ->field($field)
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