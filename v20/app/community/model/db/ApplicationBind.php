<?php
/**
 * 功能应用表格
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/23 15:44
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class ApplicationBind extends Model{

    /**
     * 获取单个应用数据信息
     * @author: wanziyang
     * @date_time: 2020/4/23 15:44
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }


    /**
     * 获取应用application_id组成的数组
     * @author: wanziyang
     * @date_time: 2020/4/28 9:35
     * @param array $where 查询条件
     * @return array|null|Model
     */
    public function getApplicationIdArr($where){
        $info = $this->where($where)->field('application_id')->select();
        return $info;
    }


    public function addOne($data) {
        return $this->insertGetId($data);
    }
}