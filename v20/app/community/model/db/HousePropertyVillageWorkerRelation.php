<?php
/**
 *人员管理
 */

namespace app\community\model\db;
use think\Model;

class HousePropertyVillageWorkerRelation extends Model
{
    public function getSum($where, $field)
    {
        $data = $this->where($where)->sum($field);
        return $data;
    }

    public function getListData($where, $field = '*', $orderby = 'id DESC', $page = 1, $page_size = 10)
    {
        $data_sql = $this->field($field)->where($where)->order($orderby);
        if ($page > 0) {
            $data_sql->page($page, $page_size);
        }
        $data = $data_sql->select();
        return $data;
    }
    public function getRelationVillageLists($where = [], $field = true, $orderby = 'r.hw_id DESC',$page=0,$limit=20) {
        $list = $this->alias('r')
            ->leftJoin('house_worker w', 'r.wid = w.wid')
            ->leftJoin('house_village v', 'w.village_id = v.village_id')
            ->field($field)->order($orderby)->where($where);
        if($page) {
            $result = $list->page($page, $limit)->select();
        }else {
            $result = $list->select();
        }
       // echo $list->getLastSql();
        return $result;
    }
    public function getCount($where = [])
    {
        $result = $this->where($where)->count();
        return $result;
    }

    public function getOneData($where, $field = true, $orderby = 'id DESC')
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