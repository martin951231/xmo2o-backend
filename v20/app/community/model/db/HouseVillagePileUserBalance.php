<?php
/**
 * Created by PhpStorm.
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseVillagePileUserBalance extends Model{
    public function getSum($where, $field)
    {
        $data = $this->where($where)->sum($field);
        return $data;
    }
    public function getListData($where, $field = true, $orderby = 'id DESC', $page = 1, $page_size = 10)
    {
        $data_sql = $this->field($field)->where($where)->order($orderby);
        if ($page > 0) {
            $data_sql->page($page, $page_size);
        }
        $data = $data_sql->select();
        return $data;
    }
    public function getCountJoinUser($where = [])
    {
        $result = $this->alias('b')->leftJoin('user u', 'b.uid=u.uid')->where($where)->count();
        //echo $this->getLastSql();
        return $result;
    }
    public function getListJoinUser($where, $field = 'b.*,u.nickname,u.phone', $orderby = 'b.id DESC', $page = 1, $page_size = 10)
    {
        $data_sql= $this->alias('b')->leftJoin('user u', 'b.uid=u.uid')->field($field)->where($where)->order($orderby);
        if ($page > 0) {
            $data_sql->page($page, $page_size);
        }
        $data = $data_sql->select();
        return $data;
    }
    public function getCount($where = [])
    {
        $result = $this->where($where)->count();
        return $result;
    }

    public function getOneData($where, $field = '*', $orderby = 'id DESC')
    {
        $data = $this->field($field)->where($where)->order($orderby)->find();
        return $data;
    }

    public function saveOneData($where, $data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    public function updateOneData($where, $data)
    {
        $result = $this->where($where)->update($data);
        return $result;
    }

    public function addOneData($data)
    {
        $result = $this->insertGetId($data);
        return $result;
    }

}
