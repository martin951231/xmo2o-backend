<?php
/**
 * @author : liukezhu
 * @date : 2021/10/12
 */
namespace app\community\model\db;

use think\Model;

class HouseVenueClassify extends Model{


    public function getList($where,$field =true,$page=1,$limit=10,$order='id ASC') {
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

    public function add($data) {
        $order_id = $this->insertGetId($data);
        return $order_id;
    }

    public function getOnes($where,$field)
    {
        $data = $this
            ->field($field)
            ->where($where)
            ->find();
        return $data;
    }

    public function save_one($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

}