<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2020/4/26 09:44
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseVillagePilePayOrder extends Model{
    /**
     * 添加缴费信息
     * @author:zhubaodi
     * @date_time: 2021/4/28 18:55
     * @param array $data 要进行添加的数据
     * @return mixed
     */
    public function add_order($data) {
        $order_id = $this->insertGetId($data);
        return $order_id;
    }

    /**
     * 获取条件下订单数量
     * @author: zhubaodi
     * @date_time: 2021/6/8 10:46
     * @param array $where 查询条件
     * @return \think\Collection
     */
    public function get_count($where) {
        $count = $this->where($where)->count();
        return $count;
    }


    /**
     * 查询对应条件缴费订单
     * @author: wanziyang
     * @date_time: 2020/4/25 15:01
     * @param array $where
     * @param bool|string $field
     * @return array|null|\think\Model
     */
    public function get_one($where,$field =true,$type=0){
        $info = $this->field($field)->where($where);
        if($type == 1){
            //查询当天
            $info->whereDay('pay_time');
        }elseif ($type == 2){
            //查询昨天
            $info->whereDay('pay_time','yesterday');
        }
        return $info->find();
    }

    public function getOne($where=[],$field=true)
    {
        $data = $this->alias('p')
            ->leftJoin('house_village_pile_equipment e','e.id=p.equipment_id')
            ->where($where)
            ->field($field)
            ->order('p.id DESC')
            ->find();
        return $data;
    }

    public function get_ones($where,$field =true,$type=0){
        $info = $this->alias('p')
            ->leftJoin('house_village_pile_equipment e','e.id=p.equipment_id')
            ->field($field)->where($where);
        if($type == 1){
            //查询当天
            $info->whereDay('p.pay_time');
        }elseif ($type == 2){
            //查询昨天
            $info->whereDay('p.pay_time','yesterday');
        }
        return $info->find();
    }


    /**
     * 修改信息
     * @author: zhubaodi
     * @date_time: 2021/5/5 14:52
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 获取订单列表
     * @author:zhubaodi
     * @date_time: 2021/5/25 10:52
     */
    public function getList($where,$field=true,$page=0,$limit=20,$order='id DESC',$type=0) {
        $list = $this->field($field)->where($where);
        if($page)
            $list = $list->order($order)->page($page,$limit)->select();
        else
            $list = $list->select();
        return $list;
    }


    //todo 统计小区与充电桩订单金额
    public function getVillageList($where,$group,$field='*',$order='o.id desc',$page=0,$limit=10){
        $list = $this->alias('o')
            ->leftJoin('house_village v','v.village_id = o.village_id')
            ->leftJoin('house_village_pile_equipment e','e.id = o.equipment_id')
            ->where($where)
            ->field($field)
            ->group($group)
            ->order($order);
        if($page)
        {
            $list->page($page,$limit);
        }
        $list = $list->select();
        return $list;
    }

    //todo 统计小区与充电桩数量
    public function getVillageCount($where,$field='*'){
        $list = $this->alias('o')
            ->leftJoin('house_village v','v.village_id = o.village_id')
            ->where($where)
            ->field($field)
            ->find();
        return $list;
    }


}
