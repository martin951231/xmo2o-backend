<?php
/**
 * 小区物业缴费列表
 * Created by PhpStorm.
 * User: wanziyang
 * Date Time: 2020/4/26 14:47
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseVillagePropertyPaylist extends Model{

    /**
     *物业缴费列表
     * @author: wanziyang
     * @date_time: 2020/4/26 14:48
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param string $order 排序
     * @return array|null|Model
     */
    public function get_list($where,$field=true,$order='id DESC') {
        $list = $this->field($field)->where($where)->order($order)->select();
        return $list;
    }

    /**
     * 添加
     * @author:wanziyang
     * @date_time:  2020/4/27 20:18
     * @param array $data 要进行添加的数据
     * @return mixed
     */
    public function add_one($data) {
        $cashier_id = $this->insertGetId($data);
        return $cashier_id;
    }

    /**
     * 获取单个社区数据信息
     * @author: wanziyang
     * @date_time: 2020/4/26 15:28
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @return mixed
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->order('add_time DESC')->find();
        return $info;
    }

    public function getValues($where,$field,$order)
    {
        $value = $this->where($where)->order($order)->value($field);
        return $value;
    }
}