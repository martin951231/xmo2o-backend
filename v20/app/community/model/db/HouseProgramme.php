<?php
/**
 * @author : liukezhu
 * @date : 2022/3/22
 */
namespace app\community\model\db;

use think\Model;

class HouseProgramme extends Model{

    public function getList($where,$field='*',$order='id desc',$page=0,$limit=20)
    {
        $sql = $this->alias('p')
            ->leftJoin('house_admin_group g','g.group_id = p.group_id')
            ->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    public function getCount($where) {
        return $this->alias('p')->leftJoin('house_admin_group g','g.group_id = p.group_id')->where($where)->count();
    }


    public function getOne($where,$field){
        return $this->field($field)->where($where)->find();
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