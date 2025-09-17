<?php
/**
 * 用户信息
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/9 10:13
 */

namespace app\community\model\db;


use think\Model;
use think\facade\Db;
class User extends Model{

    /**
     * 获取单个数据信息
     * @author: wanziyang
     * @date_time: 2020/5/9 10:23
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 添加用户
     * @author lijie
     * @date_time 2020/09/01 10:51
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }

    public function editFind($where,$data){
        $res = $this->where($where)->update($data);
        return $res;
    }

    public function getList($where,$field=true)
    {
        $data = $this->where($where)->field($field)->select();
        return $data;
    }
    
    public function getColumn($where,$column, $key = '')
    {
        $data = $this->where($where)->column($column,$key);
        return $data;
    }
}