<?php
/**
 * @author : liukezhu
 * @date : 2021/10/8
 */
namespace app\community\model\db;

use think\Model;

class HouseVenueCollectRecord extends Model{



    public function getOne($where,$field)
    {
        return $this->field($field)->where($where)->find();
    }

    public function del($where)
    {
        return $this->where($where)->delete();
    }

    public function add($data) {
        $order_id = $this->insertGetId($data);
        return $order_id;
    }


    public function getList($where,$field =true,$page=1,$limit=10,$order='r.id ASC') {
        $db_list = $this->alias('r')
            ->leftJoin('house_venue_activity a','a.id=r.activity_id')
            ->leftJoin('house_venue_classify c','c.id=a.classify_id')
            ->field($field)
            ->order($order);
        if ($page) {
            $db_list->page($page,$limit);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }

    public function getCount($where)
    {
       return $this
            ->alias('r')
            ->leftJoin('house_venue_activity a','a.id=r.activity_id')
            ->leftJoin('house_venue_classify c','c.id=a.classify_id')
            ->where($where)
            ->count();
    }


}