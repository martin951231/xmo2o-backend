<?php
/**
 * 小区公共位置
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/14 15:07
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseVillagePublicArea extends Model{
    /**
     * 条件小区公共位置表
     * @author: wanziyang
     * @date_time: 2020/5/14 15:08
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param string $order 排序
     * @return array|null|Model
     */
    public function getList($where,$field=true,$order='public_area_id ASC') {
        $list = $this->field($field)->where($where)->order($order)->select();
        return $list;
    }

    /**
     * 获取单个数据信息
     * @author: wanziyang
     * @date_time: 2020/5/15 11:39
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    public function getColumn($where, $column, $key = '')
    {
        $data = $this->where($where)->column($column,$key);
        return $data;
    }
}