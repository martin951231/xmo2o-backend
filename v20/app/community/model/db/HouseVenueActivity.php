<?php
/**
 * @author : liukezhu
 * @date : 2021/10/8
 */
namespace app\community\model\db;

use think\Model;

class HouseVenueActivity extends Model
{

    /**
     * 获取列表
     */
    public function getList($where,$field =true,$page=1,$limit=10,$order='id ASC') {
        $db_list = $this->field($field)->order($order);
        if ($page) {
            $db_list->page($page,$limit);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }


    public function getLists($where,$field =true,$page=1,$limit=10,$order='a.id ASC') {
        $db_list = $this->alias('a')
            ->leftJoin('house_venue_classify c','c.id = a.classify_id and c.village_id=a.village_id')
            ->field($field)->order($order);
        if ($page) {
            $db_list->page($page,$limit);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }

    public function getCounts($where)
    {
        $count = $this->alias('a')
            ->leftJoin('house_venue_classify c','c.id = a.classify_id and c.village_id=a.village_id')
            ->where($where)->count();
        return $count;
    }


    /**
     * 获取详情
     */
    public function getOne($where,$field)
    {
        $data = $this->alias('a')
            ->leftJoin('house_venue_classify c','c.id = a.classify_id and c.village_id=a.village_id')
            ->field($field)
            ->where($where)
            ->find();
        return $data;
    }


    public function getOnes($where,$field)
    {
        $data = $this
            ->field($field)
            ->where($where)
            ->find();
        return $data;
    }


    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    public function add($data) {
        $order_id = $this->insertGetId($data);
        return $order_id;
    }


    public function save_one($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }


    public function getAppointRecord($where,$field)
    {
        $data = $this->alias('a')
            ->leftJoin('house_venue_appoint_record r','r.activity_id = a.id')
            ->field($field)
            ->where($where)->where('r.appoint_time > a.close_time and a.close_time is not null')
            ->select();
        return $data;
    }
}