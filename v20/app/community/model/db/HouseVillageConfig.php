<?php
/**
 * 社区小区物业费积分设置表
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/23 15:44
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseVillageConfig extends Model{

    /**
     * 获取单个小区物业费积分设置
     * @author: weili
     * @date_time: 2020/7/7 11:11
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 修改信息
     * @author: weili
     * @date_time: 2020/7/7 11:15
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
     * @author : weili
     * @datetime: 2020/7/7 9:52
     * @param array $data
     * @return int
    **/
    public function addOne($data)
    {
        $insert = $this->insertGetId($data);
        return $insert;
    }

    public function getColumn($where, $column, $key = '')
    {
        $data = $this->where($where)->column($column,$key);
        return $data;
    }
}
