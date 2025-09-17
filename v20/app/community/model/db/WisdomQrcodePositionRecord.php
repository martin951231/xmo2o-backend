<?php
/**
 * @author : liukezhu
 * @date : 2022/5/23
 */
namespace app\community\model\db;

use think\Model;
class WisdomQrcodePositionRecord extends  Model
{

    public function getCount($where) {
        $count =$this->where($where)->count();
        return $count;
    }

    public function addFind($data)
    {
        $id =$this->insertGetId($data);
        return $id;
    }


    public function editFind($where,$data){
        $res = $this->where($where)->update($data);
        return $res;
    }

    public function getList($where, $field = true, $order = 'id desc', $page = 0, $limit = 10)
    {
        $sql = $this->where($where)->field($field)->order($order);
        if ($page) {
            $list = $sql->page($page, $limit)->select();
        } else {
            $list = $sql->select();
        }
        return $list;
    }

    public function query_sql($sql){
        return $this->query($sql);
    }

    public function getFind($where,$field='*',$order='id DESC'){
        $list = $this->where($where)->order($order)->field($field)->find();
        return $list;
    }

}