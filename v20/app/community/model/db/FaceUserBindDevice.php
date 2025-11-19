<?php
/**
 * 门禁设备
 * @author weili
 * @datetime 2020/8/3
 */

namespace app\community\model\db;

use think\Model;
use think\Db;
class FaceUserBindDevice extends Model
{
    /**
     * 获取一条数据
     * @param $where
     * @param $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where,$field = true,$order='id desc'){
        return $this->where($where)->field($field)->order($order)->find();
    }

    public function getOneOrder($where = [], $field = true, $order = []) {
        $result = $this->field($field)->where($where)->order($order)->find();
        return $result;
    }

    /**
     * 插入一条数据
     * @param $data
     * @return int|string
     */
    public function addData($data){
        return $this->insertGetId($data);
    }

    /**
     * 修改数据
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveData($where,$data){
        return $this->where($where)->save($data);
    }

    /**
     * Notes:获取某个字段
     * @param array  $where  条件
     * @param string $column 字段名 多个字段用逗号分隔
     * @param string $key    索引
     * @return array
     */
    public function getColumn($where, $column, $key = '')
    {
        $data = $this->where($where)->column($column,$key);
        return $data;
    }
    
    public function deleteInfo($where){
        return $this->where($where)->delete();
    }
    
    public function getOneUser($where,$field,$order='fubd.id desc')
    {
        $result = $this->alias('fubd')
            ->join('house_village_user_bind ub','ub.pigcms_id=fubd.bind_id')
            ->where($where)
            ->field($field)
            ->order($order)
            ->find();
        return $result;
    }
}