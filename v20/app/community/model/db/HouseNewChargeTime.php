<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/6/10 17:29
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseNewChargeTime extends Model{

    /**
     * 获取单个数据信息
     * @author:zhubaodi
     * @date_time: 2021/6/10 17:16
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function get_one($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 修改信息
     * @author:zhubaodi
     * @date_time: 2021/6/10 17:16
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }
    /**
     * 插入数据并获取插入id
     * @author:zhubaodi
     * @date_time: 2021/6/10 17:16
     * @paramarray $data
     **/
    public function addOne($data)
    {
        $insert = $this->insertGetId($data);
        return $insert;
    }


    public function getColumn($where,$field)
    {
        $column = $this->where($where)->column($field);
        return $column;
    }

    public function getList($where,$field)
    {
        $column = $this->where($where)->field($field)->select();
        return $column;
    }
}
