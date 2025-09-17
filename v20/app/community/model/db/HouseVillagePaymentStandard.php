<?php
/**
 * 缴费标准
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/27 20:34
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;
class HouseVillagePaymentStandard extends Model{
    /**
     * 获取单个数据信息
     * @author: wanziyang
     * @date_time: 2020/4/27 20:35
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function get_one($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 缴费相关绑定信息列表
     * @author: wanziyang
     * @date_time: 2020/4/27 20:34
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param string $order 排序
     * @return array|null|Model
     */
    public function get_list($where,$field = true,$order='standard_id desc') {
        $list = $this->field($field)->where($where)->order($order)->select();
        return $list;
    }
}