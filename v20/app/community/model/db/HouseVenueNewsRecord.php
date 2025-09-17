<?php
/**
 * @author : liukezhu
 * @date : 2021/10/9
 */
namespace app\community\model\db;

use think\Model;

class HouseVenueNewsRecord extends Model{

    public function add($data)
    {
        return $this->insertGetId($data);
    }


    public function getList($where,$field =true,$page=1,$limit=10,$order='id ASC') {
        $db_list = $this
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
        return $this->where($where)->count();
    }


}
