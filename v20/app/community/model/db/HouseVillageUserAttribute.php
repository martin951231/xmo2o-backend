<?php
/**
 * 小区业主属性
 * Created by PhpStorm.
 * Author: wanziyagn
 * Date Time: 2020/5/8 11:07
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseVillageUserAttribute extends Model{

    /**
     * 获取单个小区业主属性信息
     * @author: wanziyang
     * @date_time: 2020/5/8 11:38
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 获取小区业主属性信息
     * @author: wanziyang
     * @date_time: 2020/5/8 16:43
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @param string $order 排序规则
     * @return array|null|Model
     */
    public function getList($where,$field =true,$order='pigcms_id ASC') {
        $list = $this->field($field)->where($where)->order($order)->select();
        return $list;
    }


    public function getColumn($where,$field = 'id',$key=''){
        return $this->where($where)->column($field,$key);
    }
    
}