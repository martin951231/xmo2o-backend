<?php
/**
 * 小区单元
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/25 15:49
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;
class HouseVillageFloor extends Model{

    /**
     * 获取单个数据信息
     * @author: wanziyang
     * @date_time: 2020/4/26 15:49
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }
    public function getRawOne($whereRaw,$field=true) {
        $info = $this->field($field)->whereRaw($whereRaw)->find();
        return $info;
    }

    /**
     * 修改信息
     * @author: wanziyang
     * @date_time: 2020/4/26 15:38
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    public function addOne($data) {
        $set_info = $this->insertGetId($data);
        return $set_info;
    }

    /**
     * 单元相关信息列表
     * @author: wanziyang
     * @date_time: 2020/5/6 15:07
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param string $order 排序
     * @return array|null|Model
     */
    public function getList($where,$field = true,$order='floor_id desc') {
        $list = $this->field($field)->where($where)->order($order)->select();
        return $list;
    }

    /**
     * 获取楼栋和单元名
     * @author lijie
     * @date_time 2020/08/18 14:27
     * @param $where
     * @param $field
     * @return mixed
     */
    public function floorSingleName($where,$field='*',$order='s.id desc')
    {
        $data = $this->alias('f')
            ->leftJoin('house_village_single s','s.id = f.single_id')
            ->where($where)
            ->field($field)->order($order)
            ->select();
        return $data;
    }

    /**
     * 获取单元数量
     * @author lijie
     * @date_time 2020/01/12
     * @param array $where
     * @return int
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 获取一列值
     * User: zhanghan
     * Date: 2022/2/21
     * Time: 16:22
     * @param $where
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getOneColumn($where,$field = 'floor_id',$key=''){
        return $this->where($where)->column($field,$key);
    }

    /**
     * 查询某个字段最大值
     * @param array      $where 查询条件
     * @param string     $field 字段名
     * @param bool       $force 强制转为数字类型
     * @return mixed
     */
    public function getMax(array $where,string $field, bool $force = true)
    {
        $data = $this->where($where)->max($field, $force);
        return $data;
    }


    public function addAll($data)
    {
        $res = $this->insertAll($data);
        return $res;
    }

    public function getWhereRawColumn($where, $field = 'floor_id', $key = '')
    {
        return $this->whereRaw($where)->column($field, $key);
    }
}