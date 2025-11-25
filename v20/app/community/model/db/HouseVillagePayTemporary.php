<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/27 13:30
 */

namespace app\community\model\db;


use think\Model;
use think\facade\Db;
class HouseVillagePayTemporary extends Model{

    /**
     * 获取单个临时缴费列表数据信息
     * @author: wanziyang
     * @date_time: 2020/4/27 13:37
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function get_one($where,$field =true){
        $info = $this->alias('a')
            ->leftJoin('house_village_user_bind b','b.pigcms_id = a.bind_id')
            ->field($field)
            ->where($where)
            ->find();
        return $info;
    }

    /**
     * 添加小区临时缴费信息
     * @author:wanziyang
     * @date_time: 2020/4/27 13:37
     * @param array $data 要进行添加的数据
     * @return mixed
     */
    public function add_order($data) {
        $temporary_order_id = $this->insertGetId($data);
        return $temporary_order_id;
    }
}