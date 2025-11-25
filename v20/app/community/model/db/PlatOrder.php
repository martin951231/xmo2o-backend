<?php
/**
 * 统一订单入口
 * Created by PhpStorm.
 * Author: win7
 * Date Time: 2020/4/23 17:33
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;


class PlatOrder extends Model{

    /**
     * 获取统一订单入口信息
     * @author: wanziyang
     * @date_time: 2020/4/24 13:15
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function get_one($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 添加小区缴费信息
     * @author:zhubaodi
     * @date_time: 2021/7/22 15:55
     * @param array $data 要进行添加的数据
     * @return mixed
     */
    public function add_order($data) {
        $order_id = $this->insertGetId($data);
        return $order_id;
    }

    /**
     * 修改信息
     * @author:zhubaodi
     * @date_time: 2021/7/22 15:55
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function save_one($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }
}