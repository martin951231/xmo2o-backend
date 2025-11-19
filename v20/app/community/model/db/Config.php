<?php
/**
 * 获取系统配置项
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/29 20:59
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;
class Config extends Model{

    /**
     * 获取单个配置项信息
     * @author: wanziyang
     * @date_time: 2020/4/30 9:12
     * @param string $name 查询字段
     * @param string|bool $field 过滤字段
     * @param string $key 查询字段
     * @return array|null|Model
     */
    public function get_one($name,$field =true,$key='name'){
        $where[$key] = $name;
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 条件查询配置项
     * @author: wanziyang
     * @date_time: 2020/4/30 9:12
     * @param array $where
     * @param string|bool $field
     * @return array|null|Model
     */
    public function get_list($where,$field =true) {
        $list = $this->field($field)->where($where)->select();
        return $list;
    }


    /**
     * 获取单个配置项信息
     * @author: wanziyang
     * @date_time: 2020/4/30 9:12
     * @param string $name 查询条件
     * @param string|bool $field 过滤字段
     * @param string $key 查询字段
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }
}