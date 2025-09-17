<?php
/**
 * 投票活动
 */
namespace app\community\model\db;

use think\Model;
class PluginVote extends Model{
    public $status = [
        '0' => '禁止',
        '1' => '开启',
        '3' => '结束',
        '4' => '删除'
    ];


    public function getCount($where){
        $count = $this->where($where)->count();
        return $count;
    }


    public function getOne($where,$field =true){
        $info = $this->where($where)->field($field)->find();
        return $info;
    }

    public function getList($where,$field='*',$order='id desc',$limit){
        $list = $this->alias('v')
            ->join('house_village hv','hv.village_id=v.from_id')
            ->where($where)
            ->field($field)
            ->order($order)
            ->paginate($limit)
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
    
    public function getStatusStr($status)
    {
        return $this->status[$status] ?? '';
    }

}