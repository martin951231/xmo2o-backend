<?php
/**
 * 省市区信息
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/8 14:02
 */

namespace app\community\model\db;


use think\Model;
use think\facade\Db;
class Area extends Model{

    /**
     * 获取单个地区数据信息
     * @author: wanziyang
     * @date_time: 2020/5/8 14:03
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }
    public function saveOneData($where, $data=array())
    {
        $res = $this->where($where)->save($data);
        return $res;
    }
    /**
     * 获取地区信息
     * @author: wanziyang
     * @date_time: 2020/5/9 9:48
     * @param array $where
     * @param bool|string $field
     * @param string $order
     * @return \think\Collection
     */
    public function getList($where,$field =true,$order='area_sort DESC,area_id ASC') {
        $list = $this->field($field)->where($where)->order($order)->select();
        return $list;
    }

    /**
     * 获取地区信息
     * @author: zhubaodi
     * @date_time: 2021/4/10 10:39
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param int $type
     * @return mixed|null|Model
     */
    public function getLists($where,$field=true,$page=1,$limit=20,$order='area_sort DESC,area_id ASC') {
        $list = $this->field($field)->where($where);
        $list = $list->order($order)->page($page,$limit)->select();
        return $list;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author: zhubaodi
     * @datetime: 2021/4/12 17:20
     */
    public function getCount($where=[])
    {
        $count = $this->where($where)->count();
        return $count;
    }
    
    public function getColumns($where,$value)
    {
        $column = $this->where($where)->column($value);
        return $column;
    }
}