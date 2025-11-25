<?php
/**
 * 蓝牙开门记录
 * @author weili
 * @datetime 2020/8/4
 */

namespace app\community\model\db;

use think\Model;
class HouseVillageOpenDoor extends Model
{
    /**
     * Notes: 蓝牙门禁开门记录列表
     * @param $where
     * @param $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param int $type
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/4 20:48
     */
    public function getList($where,$field,$page=0,$limit=10,$order='o.id desc',$type=0)
    {
        $list = $this->alias('o')->leftJoin('house_village_door d','o.door_device_id=d.door_device_id')
            ->leftJoin('user u','o.uid=u.uid')
            ->where($where)
            ->field($field);
        if($type){
            $list = $list
                ->order($order)
                ->select();
        }else{
            $list = $list
                ->order($order)
                ->page($page,$limit)
                ->select();
        }
        return $list;
    }

    public function getLists($where, $field, $page = 0, $limit = 10, $order = 'id desc', $type = 0)
    {
        $list = $this->where($where)->field($field);
        if ($type) {
            $list = $list->order($order)->select();
        } else {
            $list = $list->order($order)->page($page, $limit)->select();
        }
        return $list;
    }
    

    /**
     * 获取蓝牙开门的数量
     * @author lijie
     * @date_time 2020/01/15
     * @param $where
     * @return int
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * Notes: 获取开门经纬度列表
     * @param $where
     * @param $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/4 20:59
     */
    public function getAddressList($where,$field,$page=0,$limit=10,$order='o.id desc')
    {
        $list = $this->alias('o')
            ->leftJoin('house_village_door d','o.door_device_id=d.door_device_id')
            ->leftJoin('house_village_public_area a','d.public_area_id=a.public_area_id')
            ->leftJoin('house_village_floor f','d.floor_id=f.floor_id')
            ->where($where)
            ->field($field)
            ->order($order)
            ->limit($page,$limit)
            ->select();
        return $list;
    }
}