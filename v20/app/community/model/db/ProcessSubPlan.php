<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/5/10 19:12
 */
namespace app\community\model\db;

use think\Model;
class ProcessSubPlan extends Model
{
    /**
     * 添加数据
     * @author:zhubaodi
     * @date_time: 2021/5/10 19:13
     */
    public function add($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }


    /**
     * 修改信息
     * @author:zhubaodi
     * @date_time: 2021/6/10 17:16
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function save_one($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

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

    public function addAll($data){
        $result = $this->insertAll($data);
        return $result;
    }
    
    public function deleteOne($where){
        $info = $this->where($where)->delete();
        return $info;
    }
}