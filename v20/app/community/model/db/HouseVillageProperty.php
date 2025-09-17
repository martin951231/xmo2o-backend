<?php
/**
 * 社区小区物业费缴费赠送活动
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/25 15:14
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;
class HouseVillageProperty extends Model{

    /**
     * 获取单个缴费列表数据信息
     * @author: wanziyang
     * @date_time: 2020/4/26 20:24
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function get_one($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 缴费列表
     * @author: wanziyang
     * @date_time: 2020/4/26 18:45
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param string $order 排序
     * @return array|null|Model
     */
    public function get_village_property_list($where,$field = true,$order='id desc') {
        $list = $this->field($field)->where($where)->order($order)->select();
        return $list;
    }
}