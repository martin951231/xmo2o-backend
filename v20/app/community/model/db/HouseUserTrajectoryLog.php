<?php
/**
 * @author : liukezhu
 * @date : 2021/11/12
 */
namespace app\community\model\db;
use think\Model;
use think\Db;
class HouseUserTrajectoryLog extends Model{


    public function getList($where,$field='*',$order='id desc',$page=0,$limit=10)
    {
        $sql = $this->where($where)
            ->field($field)
            ->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    public function addOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }



}