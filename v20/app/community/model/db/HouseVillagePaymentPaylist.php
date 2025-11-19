<?php
/**
 * 小区自定义缴费列表
 * Created by PhpStorm.
 * User: wanziyang
 * Date Time: 2020/4/26 14:47
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseVillagePaymentPaylist extends Model{

    /**
     * 自定义缴费列表
     * @author: wanziyang
     * @date_time: 2020/4/26 14:48
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function get_list($where,$field=true) {
        $list = $this->field($field)->where($where)->select();
        return $list;
    }

    /**
     * 添加
     * @author:wanziyang
     * @date_time:  2020/4/27 20:37
     * @param array $data 要进行添加的数据
     * @return mixed
     */
    public function add_one($data) {
        $id = $this->insertGetId($data);
        return $id;
    }
}