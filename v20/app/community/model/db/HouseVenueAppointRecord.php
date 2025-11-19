<?php
/**
 * @author : liukezhu
 * @date : 2021/10/8
 */

namespace app\community\model\db;

use think\Model;

class HouseVenueAppointRecord extends Model{


    public function getLists($where,$field =true,$page=1,$limit=10,$order='r.id ASC') {
        $db_list = $this->alias('r')
            ->leftJoin('house_venue_activity a','a.id=r.activity_id')
            ->field($field)
            ->order($order);
        if(isset($where['_appoint_time']) && !empty($where['_appoint_time'])){
            $db_list->whereDay('r.appoint_time', $where['_appoint_time']);
            unset($where['_appoint_time']);
        }
        if ($page) {
            $db_list->page($page,$limit);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }

    public function getCounts($where)
    {
        $count = $this
            ->alias('r')
            ->leftJoin('house_venue_activity a','a.id=r.activity_id');
        if(isset($where['_appoint_time']) && !empty($where['_appoint_time'])){
            $count->whereDay('r.appoint_time', $where['_appoint_time']);
            unset($where['_appoint_time']);
        }
        $count=$count ->where($where)->count();
        return $count;
    }


    public function getColumn($where,$field)
    {
        $column = $this->where($where)->column($field);
        return $column;
    }


    public function add($data)
    {
        return $this->insertGetId($data);
    }


    public function getListss($where,$field =true,$page=1,$limit=10,$order='id ASC') {
        $db_list = $this->field($field)->order($order);
        if ($page) {
            $db_list->page($page,$limit);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }


    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    public function query_sql($sql){
        return $this->query($sql);
    }


    public function getOne($where,$field =true) {
       return $this->alias('r')
           ->leftJoin('house_venue_activity a','a.id=r.activity_id')
           ->leftJoin('house_venue_classify c','c.id=a.classify_id')
           ->where($where)
           ->field($field)->find();
    }


    public function getOnes($where,$field=true)
    {
        return $this->where($where)->field($field)->find();
    }

    public function saveOne($where,$data)
    {
        $data = $this->where($where)->save($data);
        return $data;
    }

    public function getList($where, $field = true, $order = 'r.id DESC')
    {
        $data = $this->alias('r')
            ->leftJoin('house_venue_activity a','a.id=r.activity_id')
            ->field($field);
        if(isset($where['appoint_time']) && !empty($where['appoint_time'])){
            $data->whereTime('r.appoint_time', '<=', $where['appoint_time']);
            unset($where['appoint_time']);
        }
        $data=$data->where($where)->order($order)->select();
        return $data;
    }

}